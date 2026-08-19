<?php

namespace App\Domains\Marketplace\Growth\Support;

final class BrazilianPhone
{
    public static function digits(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?: '';
    }

    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = self::digits($raw);
        if ($digits === '') {
            return null;
        }

        if (! str_starts_with($digits, '55') && (strlen($digits) === 10 || strlen($digits) === 11)) {
            $digits = '55'.$digits;
        }

        return $digits;
    }

    public static function isValid(?string $raw): bool
    {
        $normalized = self::normalize((string) $raw);
        if ($normalized === null) {
            return false;
        }

        $national = str_starts_with($normalized, '55')
            ? substr($normalized, 2)
            : $normalized;

        return strlen($national) === 10 || strlen($national) === 11;
    }

    public static function format(?string $raw): ?string
    {
        $normalized = self::normalize((string) $raw);
        if ($normalized === null) {
            return null;
        }

        $national = str_starts_with($normalized, '55')
            ? substr($normalized, 2)
            : $normalized;

        if (strlen($national) === 11) {
            return sprintf('(%s) %s-%s', substr($national, 0, 2), substr($national, 2, 5), substr($national, 7));
        }

        if (strlen($national) === 10) {
            return sprintf('(%s) %s-%s', substr($national, 0, 2), substr($national, 2, 4), substr($national, 6));
        }

        return $raw;
    }
}
