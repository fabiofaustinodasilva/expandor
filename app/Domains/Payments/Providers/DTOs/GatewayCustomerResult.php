<?php

namespace App\Domains\Payments\Providers\DTOs;

readonly class GatewayCustomerResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $gatewayCustomerId,
        public array $raw = [],
    ) {}
}
