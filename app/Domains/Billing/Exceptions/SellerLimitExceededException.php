<?php

namespace App\Domains\Billing\Exceptions;

use RuntimeException;

class SellerLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly int $limit,
        public readonly int $current,
        public readonly ?string $planSlug = null,
        ?string $message = null,
    ) {
        parent::__construct($message ?? $this->defaultMessage($limit, $planSlug));
    }

    protected function defaultMessage(int $limit, ?string $planSlug): string
    {
        $hint = \App\Domains\Platform\Support\CommercialPlanCatalog::nextPlanHint($planSlug);
        $base = "Seu plano permite até {$limit} vendedores.";

        return $hint !== null ? $base.' '.$hint : $base;
    }
}
