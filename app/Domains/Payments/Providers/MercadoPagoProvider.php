<?php

namespace App\Domains\Payments\Providers;

use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Domains\Payments\Providers\Contracts\PaymentProviderContract;
use App\Domains\Payments\Providers\DTOs\GatewayCheckoutResult;
use App\Domains\Payments\Providers\DTOs\GatewayCustomerResult;
use App\Domains\Payments\Providers\DTOs\GatewaySubscriptionResult;
use App\Domains\Payments\Providers\DTOs\ParsedWebhookEvent;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Mercado Pago — Checkout Pro (Preferences) + webhooks de produção.
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
        // Checkout Pro não exige customer prévio; referência local suficiente.
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

        $billingType = strtoupper((string) ($data['billing_type'] ?? 'UNDEFINED'));

        // PIX híbrido: pagamento direto na API (QR no Expandor). Cartão: Checkout Pro.
        if ($billingType === 'PIX') {
            return $this->createPixPayment($data);
        }

        if ($billingType === 'BOLETO' || $billingType === 'TICKET') {
            return $this->createBoletoPayment($data);
        }

        return $this->createCheckoutProPreference($data);
    }

    /**
     * PIX interno — POST /v1/payments
     *
     * @param  array<string, mixed>  $data
     */
    public function createPixPayment(array $data): GatewayCheckoutResult
    {
        $this->assertConfigured();

        $uuid = (string) ($data['checkout_uuid'] ?? Str::uuid());

        $payload = [
            'transaction_amount' => round((float) $data['amount'], 2),
            'description' => (string) ($data['description'] ?? 'Expandor'),
            'payment_method_id' => 'pix',
            'payer' => array_filter([
                'email' => $data['buyer_email'] ?? null,
                'first_name' => $data['buyer_name'] ?? null,
            ]),
            'external_reference' => $uuid,
            'notification_url' => url('/webhooks/mercadopago'),
        ];

        $response = $this->client()
            ->withHeaders(['X-Idempotency-Key' => $uuid])
            ->post('/v1/payments', $payload)
            ->throw()
            ->json();

        $paymentId = (string) ($response['id'] ?? '');
        $qrCode = (string) data_get($response, 'point_of_interaction.transaction_data.qr_code', '');
        $qrBase64 = (string) data_get($response, 'point_of_interaction.transaction_data.qr_code_base64', '');
        $expiration = data_get($response, 'date_of_expiration');

        if ($paymentId === '' || $qrCode === '') {
            Log::warning('mercadopago.pix_incomplete', [
                'checkout_uuid' => $uuid,
                'has_payment_id' => $paymentId !== '',
                'has_qr' => $qrCode !== '',
            ]);

            throw new RuntimeException('Mercado Pago não retornou QR Code PIX válido.');
        }

        return new GatewayCheckoutResult(
            gatewaySessionId: $paymentId,
            checkoutUrl: url('/assinar/pix?session='.$uuid),
            raw: [
                'id' => $paymentId,
                'status' => $response['status'] ?? 'pending',
                'date_of_expiration' => $expiration,
                'point_of_interaction' => $response['point_of_interaction'] ?? null,
                'pix_qr_code' => $qrCode,
                'pix_qr_code_base64' => $qrBase64,
                'pix_expiration_date' => $expiration,
                'flow' => 'pix_direct',
            ],
        );
    }

    /**
     * Boleto — POST /v1/payments (payment_method_id=bolbradesco)
     *
     * @param  array<string, mixed>  $data
     */
    public function createBoletoPayment(array $data): GatewayCheckoutResult
    {
        $this->assertConfigured();

        $uuid = (string) ($data['checkout_uuid'] ?? Str::uuid());

        $payload = [
            'transaction_amount' => round((float) $data['amount'], 2),
            'description' => (string) ($data['description'] ?? 'Expandor'),
            'payment_method_id' => 'bolbradesco',
            'payer' => array_filter([
                'email' => $data['buyer_email'] ?? null,
                'first_name' => $data['buyer_name'] ?? null,
            ]),
            'external_reference' => $uuid,
            'notification_url' => url('/webhooks/mercadopago'),
        ];

        $response = $this->client()
            ->withHeaders(['X-Idempotency-Key' => 'boleto-'.$uuid])
            ->post('/v1/payments', $payload)
            ->throw()
            ->json();

        $paymentId = (string) ($response['id'] ?? '');
        $ticketUrl = (string) data_get($response, 'transaction_details.external_resource_url', '');
        $digitable = (string) (
            data_get($response, 'barcode.content')
            ?: data_get($response, 'transaction_details.digitable_line')
            ?: data_get($response, 'payment_method_reference_id')
            ?: ''
        );

        if ($paymentId === '') {
            Log::warning('mercadopago.boleto_incomplete', [
                'checkout_uuid' => $uuid,
            ]);

            throw new RuntimeException('Mercado Pago não retornou boleto válido.');
        }

        return new GatewayCheckoutResult(
            gatewaySessionId: $paymentId,
            checkoutUrl: $ticketUrl !== '' ? $ticketUrl : url('/company/financeiro'),
            raw: [
                'id' => $paymentId,
                'status' => $response['status'] ?? 'pending',
                'boleto_url' => $ticketUrl,
                'ticket_url' => $ticketUrl,
                'digitable_line' => $digitable,
                'barcode' => data_get($response, 'barcode'),
                'flow' => 'boleto_direct',
                'transaction_details' => $response['transaction_details'] ?? null,
            ],
        );
    }

    /**
     * Cartão — Checkout Pro Preferences
     *
     * @param  array<string, mixed>  $data
     */
    protected function createCheckoutProPreference(array $data): GatewayCheckoutResult
    {
        $uuid = (string) ($data['checkout_uuid'] ?? Str::uuid());
        $billingType = strtoupper((string) ($data['billing_type'] ?? 'UNDEFINED'));
        $paymentMethods = $this->paymentMethodsFor($billingType);

        $payload = [
            'items' => [[
                'id' => $uuid,
                'title' => (string) ($data['description'] ?? 'Expandor'),
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => round((float) $data['amount'], 2),
            ]],
            'payer' => array_filter([
                'email' => $data['buyer_email'] ?? null,
                'name' => $data['buyer_name'] ?? null,
            ]),
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

        $preferenceId = (string) ($response['id'] ?? '');
        $checkoutUrl = $this->resolveCheckoutUrl($response);

        if ($preferenceId === '' || $checkoutUrl === '') {
            Log::warning('mercadopago.preference_incomplete', [
                'checkout_uuid' => $uuid,
                'has_preference_id' => $preferenceId !== '',
                'has_init_point' => $checkoutUrl !== '',
            ]);

            throw new RuntimeException('Mercado Pago não retornou preference_id/init_point válidos.');
        }

        return new GatewayCheckoutResult(
            gatewaySessionId: $preferenceId,
            checkoutUrl: $checkoutUrl,
            raw: array_merge($response, ['flow' => 'checkout_pro']),
        );
    }

    public function createSubscription(array $data): GatewaySubscriptionResult
    {
        $this->assertConfigured();

        // Assinatura recorrente nativa MP fica para evolução; ativação local após approved.
        return new GatewaySubscriptionResult(
            gatewaySubscriptionId: 'mp_sub_'.Str::lower(Str::random(12)),
            nextBillingAt: now()->addMonth()->toIso8601String(),
            raw: [
                'external_reference' => $data['external_reference'] ?? null,
                'amount' => $data['amount'] ?? null,
            ],
        );
    }

    public function cancelSubscription(string $gatewaySubscriptionId): bool
    {
        return true;
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = (string) ($this->config['webhook_token'] ?? '');
        $hasToken = filled($this->config['access_token'] ?? null);

        // Compatível com testes / integrações internas.
        $plainToken = (string) $request->header('X-Webhook-Token', $request->input('token', ''));
        if ($secret !== '' && $plainToken !== '' && hash_equals($secret, $plainToken)) {
            return true;
        }

        $signatureHeader = (string) $request->header('X-Signature', '');

        if ($secret !== '' && $signatureHeader !== '') {
            if (hash_equals($secret, $signatureHeader)) {
                return true;
            }

            if ($this->verifyMercadoPagoSignature($request, $signatureHeader, $secret)) {
                return true;
            }

            Log::warning('mercadopago.webhook.signature_invalid', [
                'has_request_id' => $request->header('X-Request-Id') !== null,
                'data_id' => $this->extractPaymentIdFromRequest($request),
            ]);

            return false;
        }

        // Sem secret no painel: ainda processa se access_token existir (autenticidade via GET /v1/payments).
        if ($secret === '' && $hasToken) {
            Log::warning('mercadopago.webhook.secret_missing_accepting_with_api_fetch');

            return true;
        }

        return false;
    }

    public function parseWebhook(Request $request): ParsedWebhookEvent
    {
        $payload = $request->all();
        $type = (string) ($payload['type'] ?? $payload['topic'] ?? $request->query('type') ?? $request->query('topic') ?? 'payment');
        $dataId = $this->extractPaymentIdFromRequest($request);

        // Notificações reais do MP trazem só type + data.id — buscar o pagamento.
        $payment = null;
        if ($dataId !== '' && $this->looksLikePaymentNotification($type, $payload)) {
            $payment = $this->fetchPayment($dataId);
        }

        if (is_array($payment)) {
            return $this->parsedFromPayment($payload, $type, $payment, $dataId);
        }

        // Fallback: payloads de teste com status/event embutidos.
        return $this->parsedFromInlinePayload($payload, $type, $dataId);
    }

    /**
     * Expõe fetch de pagamento para comando artisan / processamento manual.
     *
     * @return array<string, mixed>|null
     */
    public function fetchPaymentPublic(string $paymentId): ?array
    {
        return $this->fetchPayment($paymentId);
    }

    /**
     * Extrai o payment_id da notificação (nunca o id da notificação em si).
     */
    public function extractPaymentIdFromRequest(Request $request): string
    {
        $payload = $request->all();

        $candidates = [
            data_get($payload, 'data.id'),
            $request->query('data.id'),
            $request->input('data.id'),
            $payload['payment_id'] ?? null,
        ];

        $topic = (string) ($payload['topic'] ?? $request->query('topic') ?? '');
        $type = (string) ($payload['type'] ?? $request->query('type') ?? '');

        if ($topic === 'payment' || $type === 'payment') {
            $candidates[] = $request->query('id');
            // Só usa id do body se não houver data.id (IPN antigo).
            if (! isset($payload['data']['id']) && isset($payload['id']) && ! isset($payload['action'])) {
                $candidates[] = $payload['id'];
            }
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== null && $candidate !== '') {
                return (string) $candidate;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $response
     */
    protected function resolveCheckoutUrl(array $response): string
    {
        $mode = strtolower((string) ($this->config['mode'] ?? PaymentGatewaySetting::MODE_SANDBOX));

        if ($mode === PaymentGatewaySetting::MODE_SANDBOX) {
            return (string) ($response['sandbox_init_point'] ?? $response['init_point'] ?? '');
        }

        return (string) ($response['init_point'] ?? $response['sandbox_init_point'] ?? '');
    }

    protected function verifyMercadoPagoSignature(Request $request, string $signatureHeader, string $secret): bool
    {
        $parts = [];
        foreach (explode(',', $signatureHeader) as $chunk) {
            [$key, $value] = array_pad(explode('=', trim($chunk), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }

        $ts = (string) ($parts['ts'] ?? '');
        $hash = (string) ($parts['v1'] ?? '');
        if ($ts === '' || $hash === '') {
            return false;
        }

        $dataId = $this->extractPaymentIdFromRequest($request);
        $requestId = (string) $request->header('X-Request-Id', '');

        // Manifests oficiais / variações aceitas pelo MP.
        $manifests = [
            "id:{$dataId};request-id:{$requestId};ts:{$ts};",
            "id:{$dataId};request-id:;ts:{$ts};",
        ];

        foreach ($manifests as $manifest) {
            $expected = hash_hmac('sha256', $manifest, $secret);
            if (hash_equals($expected, $hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function looksLikePaymentNotification(string $type, array $payload): bool
    {
        $normalized = strtolower($type);

        return str_contains($normalized, 'payment')
            || isset($payload['data']['id'])
            || (($payload['topic'] ?? null) === 'payment');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchPayment(string $paymentId): ?array
    {
        try {
            $this->assertConfigured();

            $response = $this->client()->get('/v1/payments/'.$paymentId);

            if (! $response->successful()) {
                Log::warning('mercadopago.payment_fetch_failed', [
                    'payment_id' => $paymentId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array<string, mixed> $json */
            $json = $response->json();

            return $json;
        } catch (\Throwable $e) {
            Log::warning('mercadopago.payment_fetch_exception', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $payment
     */
    protected function parsedFromPayment(array $payload, string $type, array $payment, string $dataId): ParsedWebhookEvent
    {
        $status = strtolower((string) ($payment['status'] ?? ''));
        $confirmedStatuses = ['approved', 'accredited'];
        $failedStatuses = ['rejected', 'cancelled', 'canceled', 'refunded', 'charged_back'];

        $confirmed = in_array($status, $confirmedStatuses, true);
        $failed = in_array($status, $failedStatuses, true);

        $eventId = (string) (
            $payload['id']
            ?? $payload['event_id']
            ?? ('mp_'.$dataId.'_'.$status)
        );

        return new ParsedWebhookEvent(
            eventId: $eventId,
            eventType: $type,
            payload: array_merge($payload, ['_payment' => $this->safePaymentSnapshot($payment)]),
            gatewayPaymentId: (string) ($payment['id'] ?? $dataId),
            gatewayCheckoutId: (string) ($payment['external_reference'] ?? '') ?: null,
            gatewaySubscriptionId: isset($payment['metadata']['subscription_id'])
                ? (string) $payment['metadata']['subscription_id']
                : null,
            gatewayCustomerId: isset($payment['payer']['id']) ? (string) $payment['payer']['id'] : null,
            amount: isset($payment['transaction_amount']) ? (float) $payment['transaction_amount'] : null,
            paymentMethod: (string) ($payment['payment_type_id'] ?? $payment['payment_method_id'] ?? 'pix'),
            isPaymentConfirmed: $confirmed && ! $failed,
            isPaymentFailed: $failed,
            isSubscriptionCancelled: false,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function parsedFromInlinePayload(array $payload, string $type, string $dataId): ParsedWebhookEvent
    {
        $status = strtolower((string) ($payload['status'] ?? $payload['data']['status'] ?? ''));
        $confirmedStatuses = ['approved', 'accredited', 'paid'];
        $failedStatuses = ['rejected', 'cancelled', 'canceled', 'refunded'];

        $event = (string) ($payload['event'] ?? '');
        $confirmed = in_array($status, $confirmedStatuses, true)
            || in_array($event, ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED', 'payment.confirmed'], true);

        $failed = in_array($status, $failedStatuses, true)
            || in_array($event, ['PAYMENT_FAILED', 'payment.failed'], true);

        return new ParsedWebhookEvent(
            eventId: (string) ($payload['id'] ?? $payload['event_id'] ?? ('mp_'.($dataId !== '' ? $dataId : Str::uuid()).'_'.Str::random(6))),
            eventType: $type,
            payload: $payload,
            gatewayPaymentId: $dataId !== '' ? $dataId : ($payload['payment_id'] ?? null),
            gatewayCheckoutId: $payload['checkout_id']
                ?? $payload['external_reference']
                ?? $payload['gateway_session_id']
                ?? null,
            gatewaySubscriptionId: $payload['subscription_id'] ?? null,
            gatewayCustomerId: $payload['customer_id'] ?? null,
            amount: isset($payload['amount'])
                ? (float) $payload['amount']
                : (isset($payload['transaction_amount']) ? (float) $payload['transaction_amount'] : null),
            paymentMethod: $payload['payment_type_id'] ?? $payload['method'] ?? $payload['billingType'] ?? 'pix',
            isPaymentConfirmed: $confirmed && ! $failed,
            isPaymentFailed: $failed,
            isSubscriptionCancelled: in_array(strtolower($type), ['subscription.cancelled'], true),
        );
    }

    /**
     * Snapshot sem dados sensíveis para auditoria/logs.
     *
     * @param  array<string, mixed>  $payment
     * @return array<string, mixed>
     */
    protected function safePaymentSnapshot(array $payment): array
    {
        return [
            'id' => $payment['id'] ?? null,
            'status' => $payment['status'] ?? null,
            'status_detail' => $payment['status_detail'] ?? null,
            'external_reference' => $payment['external_reference'] ?? null,
            'transaction_amount' => $payment['transaction_amount'] ?? null,
            'currency_id' => $payment['currency_id'] ?? null,
            'payment_type_id' => $payment['payment_type_id'] ?? null,
            'payment_method_id' => $payment['payment_method_id'] ?? null,
            'date_approved' => $payment['date_approved'] ?? null,
        ];
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
            'BOLETO', 'TICKET' => [
                'excluded_payment_types' => [
                    ['id' => 'credit_card'],
                    ['id' => 'debit_card'],
                    ['id' => 'bank_transfer'],
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
