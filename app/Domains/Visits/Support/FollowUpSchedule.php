<?php

namespace App\Domains\Visits\Support;

use App\Support\AppTime;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Convenção sem migration: horário 00:00:00 em scheduled_at = data sem horário definido.
 * Compatível com retornos antigos criados só com type=date.
 *
 * scheduled_at armazena dígitos de parede no fuso operacional (APP_TIMEZONE),
 * rotulados como UTC no app.timezone — ver AppTime::parseWall / formatWall.
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

        $date = AppTime::formatWall($at, 'd/m/Y') ?? '—';

        if (! self::hasTime($at)) {
            return $date;
        }

        return $date.' às '.(AppTime::formatWall($at, 'H:i') ?? '');
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

        $local = AppTime::wall($at);
        if ($local === null) {
            return false;
        }

        if (! self::hasTime($at)) {
            return $local->toDateString() < AppTime::today();
        }

        return $local->isPast();
    }

    public static function fromDateAndTime(?string $date, ?string $time): ?Carbon
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        return AppTime::parseWall(trim($date), $time);
    }

    /**
     * Normaliza entrada de API/form (Y-m-d ou datetime) para persistência.
     */
    public static function normalize(mixed $value): ?Carbon
    {
        return AppTime::parseWallValue($value);
    }
}
