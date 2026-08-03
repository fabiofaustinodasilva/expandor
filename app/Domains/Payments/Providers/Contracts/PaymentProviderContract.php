<?php

namespace App\Domains\Payments\Providers\Contracts;

use App\Domains\Payments\Providers\DTOs\GatewayCheckoutResult;
use App\Domains\Payments\Providers\DTOs\GatewayCustomerResult;
use App\Domains\Payments\Providers\DTOs\GatewaySubscriptionResult;
use App\Domains\Payments\Providers\DTOs\ParsedWebhookEvent;
use Illuminate\Http\Request;

interface PaymentProviderContract
{
    public function name(): string;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCustomer(array $data): GatewayCustomerResult;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCheckout(array $data): GatewayCheckoutResult;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSubscription(array $data): GatewaySubscriptionResult;

    public function cancelSubscription(string $gatewaySubscriptionId): bool;

    public function verifyWebhook(Request $request): bool;

    public function parseWebhook(Request $request): ParsedWebhookEvent;
}
