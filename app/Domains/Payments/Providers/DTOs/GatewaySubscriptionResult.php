<?php

namespace App\Domains\Payments\Providers\DTOs;

readonly class GatewaySubscriptionResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $gatewaySubscriptionId,
        public ?string $nextBillingAt = null,
        public array $raw = [],
    ) {}
}
