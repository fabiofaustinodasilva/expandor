<?php

namespace App\Domains\Platform\DTOs;

readonly class SaasHealthDashboardMetrics
{
    /**
     * @param  list<CompanyActivationSnapshot>  $stuckCompanies
     * @param  list<array{code: string, severity: string, message: string, company_id: int, company_name: string}>  $alerts
     */
    public function __construct(
        public int $registeredCompanies,
        public int $activatedCompanies,
        public float $activationRate,
        public ?float $averageActivationHours,
        public int $companiesInOnboarding,
        public int $stuckCompaniesCount,
        public array $stuckCompanies,
        public array $alerts,
    ) {}
}
