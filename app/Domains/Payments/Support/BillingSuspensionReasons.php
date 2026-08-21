<?php

namespace App\Domains\Payments\Support;

final class BillingSuspensionReasons
{
    public const BILLING_PAST_DUE = 'billing/past_due';

    public const ADMINISTRATIVE = 'administrative';

    public static function isFinancial(?string $reason): bool
    {
        return $reason === self::BILLING_PAST_DUE;
    }
}
