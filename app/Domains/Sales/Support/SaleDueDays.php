<?php

namespace App\Domains\Sales\Support;

final class SaleDueDays
{
    /** @var list<int> */
    public const ALLOWED = [5, 10, 15, 20, 25, 30];

    public static function isValid(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return in_array((int) $value, self::ALLOWED, true);
    }
}
