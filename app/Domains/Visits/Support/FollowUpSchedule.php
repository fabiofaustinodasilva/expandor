<?php

namespace App\Domains\Visits\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Convenção sem migration: horário 00:00:00 em scheduled_at = data sem horário definido.
 * Compatível com retornos antigos criados só com type=date.
 */
final class FollowUpSchedule
{
    public static function hasTime(?CarbonInterface $at): bool
    {
        if ($at === null) {
            return false;
        }

        return ! ($at->hour === 0 && $at->minute === 0 && $at->second === 0);
    }

    /**
     * Ex.: "04/08/2026 às 14:30" ou "04/08/2026"
     */
    public static function label(?CarbonInterface $at): string
    {
        if ($at === null) {
            return '—';
        }

        $date = $at->timezone(config('app.timezone'))->format('d/m/Y');

        if (! self::hasTime($at)) {
            return $date;
        }

        return $date.' às '.$at->timezone(config('app.timezone'))->format('H:i');
    }

    public static function timeHint(?CarbonInterface $at): ?string
    {
        if ($at === null || self::hasTime($at)) {
            return null;
        }

        return 'Horário não definido';
    }

    public static function isOverdue(?CarbonInterface $at): bool
    {
        if ($at === null) {
            return false;
        }

        $local = $at->timezone(config('app.timezone'));

        if (! self::hasTime($local)) {
            return $local->toDateString() < now()->timezone(config('app.timezone'))->toDateString();
        }

        return $local->isPast();
    }

    public static function fromDateAndTime(?string $date, ?string $time): ?Carbon
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        $date = trim($date);
        $time = $time !== null ? trim($time) : '';

        if ($time === '') {
            return Carbon::parse($date, config('app.timezone'))->startOfDay();
        }

        return Carbon::parse($date.' '.$time, config('app.timezone'));
    }

    /**
     * Normaliza entrada de API/form (Y-m-d ou datetime) para persistência.
     */
    public static function normalize(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value)->timezone(config('app.timezone'));
        }

        $raw = trim((string) $value);

        // Só data (YYYY-MM-DD) → meia-noite = sem horário
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            return Carbon::parse($raw, config('app.timezone'))->startOfDay();
        }

        $parsed = Carbon::parse($raw, config('app.timezone'));

        // datetime-local / ISO sem segundos às vezes chega com 00:00 intencional
        return $parsed;
    }
}
