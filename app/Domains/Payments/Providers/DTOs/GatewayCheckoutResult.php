<?php

namespace App\Domains\Payments\Providers\DTOs;

readonly class GatewayCheckoutResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $gatewaySessionId,
        public string $checkoutUrl,
        public array $raw = [],
    ) {}
}
