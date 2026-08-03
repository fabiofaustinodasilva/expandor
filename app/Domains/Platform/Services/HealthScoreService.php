<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Actions\CalculateHealthScoreAction;
use App\Domains\Platform\DTOs\CompanyHealthDTO;
use App\Domains\Platform\Enums\HealthRiskLevel;
use App\Domains\Platform\Models\CompanyHealthScore;
use App\Domains\Platform\Repositories\PlatformConsoleRepository;
use Illuminate\Support\Collection;

class HealthScoreService
{
    public function __construct(
        protected CalculateHealthScoreAction $calculateAction,
        protected PlatformConsoleRepository $repository,
    ) {}

    public function calculate(Company $company, ?User $actor = null): CompanyHealthDTO
    {
        return $this->calculateAction->execute($company, $actor);
    }

    /**
     * @return Collection<int, CompanyHealthDTO>
     */
    public function recalculateAll(?User $actor = null): Collection
    {
        return Company::query()
            ->where('is_system', false)
            ->get()
            ->map(fn (Company $company) => $this->calculate($company, $actor));
    }

    public function forCompany(Company $company): ?CompanyHealthScore
    {
        return CompanyHealthScore::query()->where('company_id', $company->id)->first();
    }

    /**
     * @return array{average:?float, healthy:int, medium:int, at_risk:int, critical:int}
     */
    public function aggregate(): array
    {
        return [
            'average' => $this->repository->averageHealthScore(),
            'healthy' => $this->repository->countByRisk(HealthRiskLevel::Healthy),
            'medium' => $this->repository->countByRisk(HealthRiskLevel::Medium),
            'at_risk' => $this->repository->countByRisk(HealthRiskLevel::AtRisk),
            'critical' => $this->repository->countByRisk(HealthRiskLevel::Critical),
        ];
    }
}
