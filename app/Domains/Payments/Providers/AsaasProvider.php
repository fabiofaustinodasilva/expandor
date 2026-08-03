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

class AsaasProvider implements PaymentProviderContract
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {}

    public function name(): string
    {
        return 'asaas';
    }

    public function createCustomer(array $data): GatewayCustomerResult
    {
        $response = $this->client()->post('/customers', [
            'name' => $data['name'],
            'email' => $data['email'],
            'cpfCnpj' => preg_replace('/\D+/', '', (string) ($data['document'] ?? '')) ?: null,
            'phone' => preg_replace('/\D+/', '', (string) ($data['phone'] ?? '')) ?: null,
            'externalReference' => $data['external_reference'] ?? null,
        ])->throw()->json();

        return new GatewayCustomerResult(
            gatewayCustomerId: (string) $response['id'],
            raw: $response,
        );
    }

    public function createCheckout(array $data): GatewayCheckoutResult
    {
        $response = $this->client()->post('/payments', [
            'customer' => $data['gateway_customer_id'],
            'billingType' => strtoupper((string) ($data['billing_type'] ?? 'UNDEFINED')),
            'value' => (float) $data['amount'],
            'dueDate' => now()->addDays(3)->toDateString(),
            'description' => $data['description'] ?? 'Expandor',
            'externalReference' => $data['checkout_uuid'] ?? null,
            'callback' => [
                'successUrl' => $data['success_url'] ?? url('/checkout/success'),
                'autoRedirect' => true,
            ],
        ])->throw()->json();

        $checkoutUrl = $response['invoiceUrl']
            ?? $response['bankSlipUrl']
            ?? url('/checkout/success?session='.($data['checkout_uuid'] ?? ''));

        return new GatewayCheckoutResult(
            gatewaySessionId: (string) $response['id'],
            checkoutUrl: (string) $checkoutUrl,
            raw: $response,
        );
    }

    public function createSubscription(array $data): GatewaySubscriptionResult
    {
        $cycle = strtoupper((string) ($data['billing_cycle'] ?? 'MONTHLY'));

        $response = $this->client()->post('/subscriptions', [
            'customer' => $data['gateway_customer_id'],
            'billingType' => strtoupper((string) ($data['billing_type'] ?? 'UNDEFINED')),
            'value' => (float) $data['amount'],
            'nextDueDate' => now()->addMonth()->toDateString(),
            'cycle' => $cycle === 'YEARLY' ? 'YEARLY' : 'MONTHLY',
            'description' => $data['description'] ?? 'Expandor',
            'externalReference' => $data['external_reference'] ?? null,
        ])->throw()->json();

        return new GatewaySubscriptionResult(
            gatewaySubscriptionId: (string) $response['id'],
            nextBillingAt: $response['nextDueDate'] ?? null,
            raw: $response,
        );
    }

    public function cancelSubscription(string $gatewaySubscriptionId): bool
    {
        $this->client()->delete('/subscriptions/'.$gatewaySubscriptionId)->throw();

        return true;
    }

    public function verifyWebhook(Request $request): bool
    {
        $expected = (string) ($this->config['webhook_token'] ?? '');

        if ($expected === '') {
            return false;
        }

        $token = (string) $request->header('asaas-access-token', $request->header('X-Webhook-Token', ''));

        return hash_equals($expected, $token);
    }

    public function parseWebhook(Request $request): ParsedWebhookEvent
    {
        $payload = $request->all();
        $eventType = (string) ($payload['event'] ?? 'UNKNOWN');
        $payment = (array) ($payload['payment'] ?? []);

        $confirmed = in_array($eventType, [
            'PAYMENT_CONFIRMED',
            'PAYMENT_RECEIVED',
            'PAYMENT_APPROVED_BY_RISK_ANALYSIS',
        ], true);

        $failed = in_array($eventType, [
            'PAYMENT_OVERDUE',
            'PAYMENT_DELETED',
            'PAYMENT_REFUNDED',
            'PAYMENT_CHARGEBACK_REQUESTED',
        ], true);

        return new ParsedWebhookEvent(
            eventId: (string) ($payload['id'] ?? ($payment['id'] ?? Str::uuid()).'|'.$eventType),
            eventType: $eventType,
            payload: $payload,
            gatewayPaymentId: isset($payment['id']) ? (string) $payment['id'] : null,
            gatewayCheckoutId: isset($payment['externalReference'])
                ? (string) $payment['externalReference']
                : (isset($payment['id']) ? (string) $payment['id'] : null),
            gatewaySubscriptionId: isset($payment['subscription']) ? (string) $payment['subscription'] : null,
            gatewayCustomerId: isset($payment['customer']) ? (string) $payment['customer'] : null,
            amount: isset($payment['value']) ? (float) $payment['value'] : null,
            paymentMethod: isset($payment['billingType']) ? strtolower((string) $payment['billingType']) : null,
            isPaymentConfirmed: $confirmed,
            isPaymentFailed: $failed,
            isSubscriptionCancelled: $eventType === 'SUBSCRIPTION_DELETED',
        );
    }

    protected function client(): PendingRequest
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/');

        if ($apiKey === '' || $baseUrl === '') {
            throw new RuntimeException('Asaas provider is not configured.');
        }

        return Http::baseUrl($baseUrl)
            ->timeout((int) ($this->config['timeout'] ?? 30))
            ->withHeaders([
                'access_token' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
    }
}
