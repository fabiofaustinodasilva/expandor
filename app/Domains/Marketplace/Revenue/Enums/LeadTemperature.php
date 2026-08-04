<?php

namespace App\Domains\Marketplace\Revenue\Enums;

enum LeadTemperature: string
{
    case Cold = 'cold';
    case Warm = 'warm';
    case Hot = 'hot';

    public function label(): string
    {
        return match ($this) {
            self::Cold => 'Frio',
            self::Warm => 'Morno',
            self::Hot => 'Quente',
        };
    }

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 71 => self::Hot,
            $score >= 31 => self::Warm,
            default => self::Cold,
        };
    }
}
