<?php

namespace App\Domains\Payments\Providers;

use App\Domains\Payments\Providers\Contracts\PaymentProviderContract;
use App\Domains\Payments\Providers\DTOs\GatewayCheckoutResult;
use App\Domains\Payments\Providers\DTOs\GatewayCustomerResult;
use App\Domains\Payments\Providers\DTOs\GatewaySubscriptionResult;
use App\Domains\Payments\Providers\DTOs\ParsedWebhookEvent;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Mercado Pago — Preferences API + webhooks (Sprint 7.1).
 */
class MercadoPagoProvider implements PaymentProviderContract
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {}

    public function name(): string
    {
        return 'mercadopago';
    }

    public function createCustomer(array $data): GatewayCustomerResult
    {
        // Preferências MP não exigem customer prévio; usamos referência local.
        return new GatewayCustomerResult(
            gatewayCustomerId: 'mp_cus_'.Str::lower(Str::random(16)),
            raw: [
                'email' => $data['email'] ?? null,
                'name' => $data['name'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
            ],
        );
    }

    public function createCheckout(array $data): GatewayCheckoutResult
    {
        $this->assertConfigured();

        $uuid = (string) ($data['checkout_uuid'] ?? Str::uuid());
        $billingType = strtoupper((string) ($data['billing_type'] ?? 'UNDEFINED'));
        $paymentMethods = $this->paymentMethodsFor($billingType);

        $payload = [
            'items' => [[
                'id' => $uuid,
                'title' => (string) ($data['description'] ?? 'Expandor'),
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => (float) $data['amount'],
            ]],
            'payer' => [
                'email' => $data['buyer_email'] ?? null,
                'name' => $data['buyer_name'] ?? null,
            ],
            'external_reference' => $uuid,
            'notification_url' => url('/webhooks/mercadopago'),
            'back_urls' => [
                'success' => $data['success_url'] ?? url('/checkout/success'),
                'pending' => $data['success_url'] ?? url('/checkout/success'),
                'failure' => $data['cancel_url'] ?? url('/checkout/cancel'),
            ],
            'auto_return' => 'approved',
            'metadata' => [
                'checkout_uuid' => $uuid,
                'billing_type' => $billingType,
            ],
        ];

        if ($paymentMethods !== null) {
            $payload['payment_methods'] = $paymentMethods;
        }

        $response = $this->client()->post('/checkout/preferences', $payload)->throw()->json();

        $checkoutUrl = (string) ($response['init_point']
            ?? $response['sandbox_init_point']
            ?? url('/assinar/aguardando?session='.$uuid));

        return new GatewayCheckoutResult(
            gatewaySessionId: (string) ($response['id'] ?? ('mp_pref_'.$uuid)),
            checkoutUrl: $checkoutUrl,
            raw: $response,
        );
    }

    public function createSubscription(array $data): GatewaySubscriptionResult
    {
        $this->assertConfigured();

        // Fundação: assinatura via preference recorrente futura; ID sintético por enquanto.
        return new GatewaySubscriptionResult(
            gatewaySubscriptionId: 'mp_sub_'.Str::lower(Str::random(12)),
            nextBillingAt: now()->addMonth()->toIso8601String(),
            raw: $data,
        );
    }

    public function cancelSubscription(string $gatewaySubscriptionId): bool
    {
        return true;
    }

    public function verifyWebhook(Request $request): bool
    {
        $expected = (string) ($this->config['webhook_token'] ?? '');
        if ($expected === '') {
            // Sem token configurado: rejeita (segurança — nunca confiar no browser).
            return false;
        }

        $token = (string) $request->header('X-Webhook-Token', $request->header('X-Signature', $request->input('token', '')));

        return hash_equals($expected, $token);
    }

    public function parseWebhook(Request $request): ParsedWebhookEvent
    {
        $payload = $request->all();
        $type = (string) ($payload['type'] ?? $payload['action'] ?? $payload['topic'] ?? 'payment');
        $dataId = (string) ($payload['data']['id'] ?? $payload['id'] ?? $payload['payment_id'] ?? Str::uuid());
        $status = strtolower((string) ($payload['status'] ?? $payload['data']['status'] ?? ''));

        $confirmedStatuses = ['approved', 'accredited', 'paid'];
        $failedStatuses = ['rejected', 'cancelled', 'refunded'];

        $event = (string) ($payload['event'] ?? '');
        $confirmed = in_array($status, $confirmedStatuses, true)
            || $event === 'PAYMENT_CONFIRMED'
            || $event === 'payment.confirmed';

        $failed = in_array($status, $failedStatuses, true)
            || in_array($event, ['PAYMENT_FAILED', 'payment.failed'], true);

        return new ParsedWebhookEvent(
            eventId: (string) ($payload['id'] ?? $payload['event_id'] ?? ('mp_'.$dataId.'_'.Str::random(6))),
            eventType: $type,
            payload: $payload,
            gatewayPaymentId: $dataId !== '' ? $dataId : ($payload['payment_id'] ?? null),
            gatewayCheckoutId: $payload['checkout_id']
                ?? $payload['external_reference']
                ?? $payload['gateway_session_id']
                ?? null,
            gatewaySubscriptionId: $payload['subscription_id'] ?? null,
            gatewayCustomerId: $payload['customer_id'] ?? null,
            amount: isset($payload['amount']) ? (float) $payload['amount'] : (isset($payload['transaction_amount']) ? (float) $payload['transaction_amount'] : null),
            paymentMethod: $payload['payment_type_id'] ?? $payload['method'] ?? $payload['billingType'] ?? 'pix',
            isPaymentConfirmed: $confirmed && ! $failed,
            isPaymentFailed: $failed,
            isSubscriptionCancelled: in_array(strtolower($type), ['subscription.cancelled'], true),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function paymentMethodsFor(string $billingType): ?array
    {
        return match ($billingType) {
            'PIX' => [
                'excluded_payment_types' => [
                    ['id' => 'credit_card'],
                    ['id' => 'debit_card'],
                    ['id' => 'ticket'],
                ],
            ],
            'CREDIT_CARD', 'CARD' => [
                'excluded_payment_types' => [
                    ['id' => 'ticket'],
                    ['id' => 'bank_transfer'],
                ],
            ],
            default => null,
        };
    }

    protected function client(): PendingRequest
    {
        $token = (string) ($this->config['access_token'] ?? '');
        $base = rtrim((string) ($this->config['base_url'] ?? 'https://api.mercadopago.com'), '/');

        return Http::baseUrl($base)
            ->withToken($token)
            ->acceptJson()
            ->timeout((int) ($this->config['timeout'] ?? 30));
    }

    protected function assertConfigured(): void
    {
        if (empty($this->config['access_token'])) {
            throw new RuntimeException('Mercado Pago access token não configurado.');
        }
    }
}
