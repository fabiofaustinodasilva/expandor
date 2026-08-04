<?php

namespace App\Domains\Marketplace\Growth\DTOs;

readonly class GrowthDashboardMetrics
{
    /**
     * @param  array<int, array{label: string, value: int|float}>  $funnel
     * @param  array<int, array{label: string, value: int}>  $topSources
     * @param  array<int, array{label: string, value: int}>  $topCampaigns
     * @param  array<int, array{label: string, value: int}>  $topPages
     */
    public function __construct(
        public int $visitsToday,
        public int $visitsWeek,
        public int $visitsMonth,
        public int $totalVisits,
        public int $uniqueVisitors,
        public int $totalLeads,
        public int $trialsStarted,
        public int $customersConverted,
        public float $conversionRate,
        public float $signupConversion,
        public array $funnel,
        public array $topSources,
        public array $topCampaigns,
        public array $topPages,
    ) {}
}
