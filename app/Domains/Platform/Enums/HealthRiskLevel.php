<?php

namespace App\Domains\Platform\Enums;

enum HealthRiskLevel: string
{
    case Healthy = 'healthy';
    case Medium = 'medium';
    case AtRisk = 'at_risk';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Saudável',
            self::Medium => 'Atenção',
            self::AtRisk => 'Em risco',
            self::Critical => 'Crítico',
        };
    }

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 80 => self::Healthy,
            $score >= 60 => self::Medium,
            $score >= 40 => self::AtRisk,
            default => self::Critical,
        };
    }
}
