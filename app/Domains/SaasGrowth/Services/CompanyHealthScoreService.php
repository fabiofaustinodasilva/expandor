<?php

namespace App\Domains\SaasGrowth\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Models\CompanyHealthScore;
use App\Domains\Platform\Services\HealthScoreService;
use App\Domains\SaasGrowth\Enums\HealthClassification;
use App\Domains\Security\Services\SecurityService;

class CompanyHealthScoreService
{
    public function __construct(
        protected HealthScoreService $health,
        protected SecurityService $security,
    ) {}

    public function calculate(Company $company, ?User $actor = null): CompanyHealthScore
    {
        $dto = $this->health->calculate($company, $actor);
        $classification = HealthClassification::fromScore($dto->score);

        $row = CompanyHealthScore::query()->where('company_id', $company->id)->firstOrFail();
        $row->classification = $classification->value;
        $row->save();

        $this->security->recordAudit(
            action: 'health_score_calculated',
            user: $actor,
            auditable: $company,
            newValues: [
                'score' => $row->score,
                'classification' => $classification->value,
            ],
            companyId: $company->id,
        );

        return $row->fresh() ?? $row;
    }

    public function classificationForScore(int $score): HealthClassification
    {
        return HealthClassification::fromScore($score);
    }

    public function averageScore(): float
    {
        return round((float) (CompanyHealthScore::query()->avg('score') ?? 0), 1);
    }

    public function countAtRisk(): int
    {
        return CompanyHealthScore::query()
            ->where(function ($q): void {
                $q->where('classification', HealthClassification::Risk->value)
                    ->orWhere('score', '<=', 30);
            })
            ->count();
    }
}
