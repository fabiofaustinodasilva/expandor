<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;

/**
 * Display / business timezone helpers.
 *
 * Storage: config('app.timezone') remains UTC. Values written via now() are UTC wall clocks.
 * Presentation + civil-day boundaries: config('app.display_timezone') via APP_TIMEZONE.
 *
 * Instant fields (last_seen_at, visited_at, created_at from now(), audit, commissions…):
 *   AppTime::formatInstant() / local() / dayBoundsUtc().
 *
 * Wall-clock fields (follow_ups.scheduled_at from forms — historical digits are business-local):
 *   AppTime::formatWall() / parseWall() / today() with whereDate.
 *
 * DATE-only (campaigns.start_date / end_date): do not pass through AppTime conversion.
 */
final class AppTime
{
    public static function zone(): string
    {
        return (string) config('app.display_timezone', 'America/Sao_Paulo');
    }

    public static function now(): Carbon
    {
        return now(self::zone());
    }

    /**
     * Operational calendar day (Y-m-d) in the display timezone.
     */
    public static function today(): string
    {
        return self::now()->toDateString();
    }

    public static function yesterday(): string
    {
        return self::now()->copy()->subDay()->toDateString();
    }

    /**
     * Inclusive UTC bounds for the operational day (for TIMESTAMP/DATETIME instant columns).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dayBoundsUtc(?string $date = null): array
    {
        $day = $date ?? self::today();
        $start = Carbon::parse($day, self::zone())->startOfDay()->utc();
        $end = Carbon::parse($day, self::zone())->endOfDay()->utc();

        return [$start, $end];
    }

    /**
     * Convert a UTC-stored instant to the display timezone.
     */
    public static function local(?CarbonInterface $at): ?Carbon
    {
        if ($at === null) {
            return null;
        }

        return Carbon::instance($at)->timezone(self::zone());
    }

    public static function formatInstant(
        CarbonInterface|DateTimeInterface|string|null $at,
        string $format = 'd/m/Y H:i'
    ): ?string {
        $carbon = self::asCarbon($at);
        if ($carbon === null) {
            return null;
        }

        return $carbon->timezone(self::zone())->format($format);
    }

    /**
     * Format wall-clock datetimes (follow-ups): digits are already business-local.
     */
    public static function formatWall(
        CarbonInterface|DateTimeInterface|string|null $at,
        string $format = 'd/m/Y H:i'
    ): ?string {
        $carbon = self::asCarbon($at);
        if ($carbon === null) {
            return null;
        }

        return $carbon->copy()->shiftTimezone(self::zone())->format($format);
    }

    public static function wall(?CarbonInterface $at): ?Carbon
    {
        if ($at === null) {
            return null;
        }

        return Carbon::instance($at)->shiftTimezone(self::zone());
    }

    /**
     * Parse user-entered date/time as business-local wall clock for persistence.
     * Digits are stored unchanged (compatible with historical follow-ups).
     */
    public static function parseWall(string $date, ?string $time = null): Carbon
    {
        $date = trim($date);
        $time = $time !== null ? trim($time) : '';

        if ($time === '') {
            return Carbon::parse($date, self::zone())->startOfDay()->shiftTimezone('UTC');
        }

        return Carbon::parse($date.' '.$time, self::zone())->shiftTimezone('UTC');
    }

    public static function parseWallValue(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            // Keep wall digits; label as UTC for storage consistency.
            return Carbon::instance($value)->shiftTimezone('UTC');
        }

        $raw = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            return Carbon::parse($raw, self::zone())->startOfDay()->shiftTimezone('UTC');
        }

        // Strip Z / offset so form values are treated as wall clocks, not true instants.
        $raw = preg_replace('/(Z|[+-]\d{2}:?\d{2})$/i', '', $raw) ?? $raw;
        $raw = str_replace('T', ' ', $raw);

        return Carbon::parse($raw, self::zone())->shiftTimezone('UTC');
    }

    public static function isTodayInstant(?CarbonInterface $at): bool
    {
        $local = self::local($at);
        if ($local === null) {
            return false;
        }

        return $local->toDateString() === self::today();
    }

    public static function isYesterdayInstant(?CarbonInterface $at): bool
    {
        $local = self::local($at);
        if ($local === null) {
            return false;
        }

        return $local->toDateString() === self::yesterday();
    }

    public static function asCarbon(CarbonInterface|DateTimeInterface|string|null $at): ?Carbon
    {
        if ($at === null || $at === '') {
            return null;
        }

        if ($at instanceof CarbonInterface) {
            return Carbon::instance($at);
        }

        if ($at instanceof DateTimeInterface) {
            return Carbon::parse($at->format('Y-m-d H:i:s.u'), config('app.timezone'));
        }

        return Carbon::parse((string) $at, config('app.timezone'));
    }
}
