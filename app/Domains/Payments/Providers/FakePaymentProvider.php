<?php

namespace App\Domains\Payments\Providers;

use App\Domains\Payments\Providers\Contracts\PaymentProviderContract;
use App\Domains\Payments\Providers\DTOs\GatewayCheckoutResult;
use App\Domains\Payments\Providers\DTOs\GatewayCustomerResult;
use App\Domains\Payments\Providers\DTOs\GatewaySubscriptionResult;
use App\Domains\Payments\Providers\DTOs\ParsedWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class FakePaymentProvider implements PaymentProviderContract
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {}

    public function name(): string
    {
        return 'fake';
    }

    public function createCustomer(array $data): GatewayCustomerResult
    {
        return new GatewayCustomerResult(
            gatewayCustomerId: 'fake_cus_'.Str::lower(Str::random(12)),
            raw: $data,
        );
    }

    public function createCheckout(array $data): GatewayCheckoutResult
    {
        $sessionId = 'fake_sess_'.Str::lower(Str::random(12));
        $uuid = (string) ($data['checkout_uuid'] ?? Str::uuid());
        $billingType = strtoupper((string) ($data['billing_type'] ?? 'UNDEFINED'));

        $checkoutUrl = $billingType === 'PIX'
            ? url('/assinar/aguardando?session='.$uuid)
            : url('/checkout/success?session='.$uuid.'&provider=fake');

        return new GatewayCheckoutResult(
            gatewaySessionId: $sessionId,
            checkoutUrl: $checkoutUrl,
            raw: [
                'session_id' => $sessionId,
                'amount' => $data['amount'] ?? 0,
                'billing_type' => $billingType,
            ],
        );
    }

    public function createSubscription(array $data): GatewaySubscriptionResult
    {
        return new GatewaySubscriptionResult(
            gatewaySubscriptionId: 'fake_sub_'.Str::lower(Str::random(12)),
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
        $expected = (string) ($this->config['webhook_token'] ?? 'fake-webhook-token');
        $token = (string) $request->header('X-Webhook-Token', $request->input('token', ''));

        return hash_equals($expected, $token);
    }

    public function parseWebhook(Request $request): ParsedWebhookEvent
    {
        $payload = $request->all();
        $eventType = (string) ($payload['event'] ?? $payload['event_type'] ?? 'PAYMENT_CONFIRMED');

        return new ParsedWebhookEvent(
            eventId: (string) ($payload['id'] ?? $payload['event_id'] ?? Str::uuid()),
            eventType: $eventType,
            payload: $payload,
            gatewayPaymentId: $payload['payment_id'] ?? null,
            gatewayCheckoutId: $payload['checkout_id'] ?? $payload['gateway_session_id'] ?? null,
            gatewaySubscriptionId: $payload['subscription_id'] ?? null,
            gatewayCustomerId: $payload['customer_id'] ?? null,
            amount: isset($payload['amount']) ? (float) $payload['amount'] : null,
            paymentMethod: $payload['billingType'] ?? $payload['method'] ?? 'pix',
            isPaymentConfirmed: in_array($eventType, ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED', 'payment.confirmed'], true),
            isPaymentFailed: in_array($eventType, ['PAYMENT_FAILED', 'payment.failed'], true),
            isSubscriptionCancelled: in_array($eventType, ['SUBSCRIPTION_CANCELLED', 'subscription.cancelled'], true),
        );
    }

    public function assertConfigured(): void
    {
        if (! app()->environment('testing') && empty($this->config['webhook_token'])) {
            throw new RuntimeException('Fake payment provider is not configured.');
        }
    }
}
