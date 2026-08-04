<?php

namespace App\Domains\SaasGrowth\Enums;

enum HealthClassification: string
{
    case Risk = 'risk';
    case Attention = 'attention';
    case Healthy = 'healthy';

    public function label(): string
    {
        return match ($this) {
            self::Risk => 'Risco',
            self::Attention => 'Atenção',
            self::Healthy => 'Saudável',
        };
    }

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 71 => self::Healthy,
            $score >= 31 => self::Attention,
            default => self::Risk,
        };
    }
}
