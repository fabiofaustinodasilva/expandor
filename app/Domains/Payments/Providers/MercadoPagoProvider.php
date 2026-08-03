<?php

namespace App\Domains\Payments\Providers;

use App\Domains\Payments\Providers\Contracts\PaymentProviderContract;
use App\Domains\Payments\Providers\DTOs\GatewayCheckoutResult;
use App\Domains\Payments\Providers\DTOs\GatewayCustomerResult;
use App\Domains\Payments\Providers\DTOs\GatewaySubscriptionResult;
use App\Domains\Payments\Providers\DTOs\ParsedWebhookEvent;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Stub — prepared for future Mercado Pago integration.
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
        throw $this->notImplemented();
    }

    public function createCheckout(array $data): GatewayCheckoutResult
    {
        throw $this->notImplemented();
    }

    public function createSubscription(array $data): GatewaySubscriptionResult
    {
        throw $this->notImplemented();
    }

    public function cancelSubscription(string $gatewaySubscriptionId): bool
    {
        throw $this->notImplemented();
    }

    public function verifyWebhook(Request $request): bool
    {
        return false;
    }

    public function parseWebhook(Request $request): ParsedWebhookEvent
    {
        throw $this->notImplemented();
    }

    protected function notImplemented(): RuntimeException
    {
        return new RuntimeException('Mercado Pago provider is prepared but not implemented yet.');
    }
}
