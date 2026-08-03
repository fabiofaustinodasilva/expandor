<?php

namespace App\Domains\Platform\DTOs;

class PlatformDashboardMetrics
{
    /**
     * @param  array<string, array{count:int, mrr:float|int}>  $clientsByPlan
     * @param  array<string, array{count:int, mrr:float|int}>  $revenueByPlan
     */
    public function __construct(
        public readonly int $activeCompanies,
        public readonly int $totalUsers,
        public readonly int $totalProperties,
        public readonly int $aiTokensConsumed,
        public readonly int $messagesSent,
        public readonly float $mrr = 0,
        public readonly float $arr = 0,
        public readonly float $monthlyRevenue = 0,
        public readonly float $yearlyRevenue = 0,
        public readonly int $trialClients = 0,
        public readonly int $suspendedClients = 0,
        public readonly int $pastDueClients = 0,
        public readonly array $clientsByPlan = [],
        public readonly array $revenueByPlan = [],
        public readonly int $onboardingInProgress = 0,
        public readonly int $onboardingCompleted = 0,
        public readonly ?float $onboardingAvgHours = null,
        public readonly int $onboardingStuck = 0,
        public readonly float $onboardingCompletionRate = 0,
        public readonly ?float $averageHealthScore = null,
        public readonly int $healthyCompanies = 0,
        public readonly int $atRiskCompanies = 0,
        public readonly int $criticalCompanies = 0,
        public readonly int $activeImpersonations = 0,
        public readonly int $totalCompanies = 0,
        public readonly int $cancelledClients = 0,
        public readonly float $churnRate = 0,
        public readonly float $trialConversionRate = 0,
    ) {}
}
