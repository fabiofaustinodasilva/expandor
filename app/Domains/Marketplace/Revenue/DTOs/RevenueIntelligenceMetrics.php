<?php

namespace App\Domains\Marketplace\Revenue\DTOs;

readonly class RevenueIntelligenceMetrics
{
    /**
     * @param  array<int, array{label: string, value: int|float}>  $funnel
     * @param  array<int, array{campaign: string, investment: float, leads: int, customers: int, cac: float|null, roi: float|null}>  $campaigns
     * @param  array<int, array{id: int, name: string, score: int, source: ?string, utm: ?string}>  $hotLeads
     */
    public function __construct(
        public int $visitors,
        public int $leads,
        public float $conversionRate,
        public int $hotLeadsCount,
        public int $demosRequested,
        public int $trialsStarted,
        public int $customersConverted,
        public array $funnel,
        public array $campaigns,
        public array $hotLeads,
    ) {}
}
