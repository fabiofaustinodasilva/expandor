<?php

namespace App\Domains\Platform\Enums;

enum ActivationHealthStatus: string
{
    case Healthy = 'healthy';
    case Attention = 'attention';
    case AtRisk = 'at_risk';

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Saudável',
            self::Attention => 'Atenção',
            self::AtRisk => 'Em risco',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Healthy => '🟢',
            self::Attention => '🟡',
            self::AtRisk => '🔴',
        };
    }

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 80 => self::Healthy,
            $score >= 50 => self::Attention,
            default => self::AtRisk,
        };
    }
}
