<?php

namespace App\Domains\SaasGrowth\DTOs;

readonly class SaasIntelligenceMetrics
{
    /**
     * @param  array<int, array{company_id: int, name: string, score: int, classification: string}>  $atRisk
     * @param  array<int, array{company_id: int, name: string, metric: string, percent: float, message: string}>  $upgradeHints
     */
    public function __construct(
        public int $totalCompanies,
        public int $activeCompanies,
        public int $activeTrials,
        public int $convertedTrials,
        public float $trialConversionRate,
        public float $averageHealth,
        public int $companiesAtRisk,
        public float $estimatedMrr,
        public array $atRisk,
        public array $upgradeHints,
    ) {}
}
