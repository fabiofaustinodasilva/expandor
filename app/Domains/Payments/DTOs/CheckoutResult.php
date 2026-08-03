<?php

namespace App\Domains\Payments\DTOs;

use App\Domains\Payments\Models\CheckoutSession;

readonly class CheckoutResult
{
    public function __construct(
        public CheckoutSession $session,
        public string $checkoutUrl,
    ) {}
}
