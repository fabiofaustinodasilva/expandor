<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\DTOs\CompanyHealthDTO;
use App\Domains\Platform\Enums\HealthRiskLevel;
use App\Domains\Platform\Repositories\PlatformConsoleRepository;
use App\Domains\Security\Services\SecurityService;
use Carbon\Carbon;

class CalculateHealthScoreAction
{
    public function __construct(
        protected PlatformConsoleRepository $repository,
        protected SecurityService $security,
    ) {}

    public function execute(Company $company, ?User $actor = null): CompanyHealthDTO
    {
        $factors = $this->repository->healthFactors($company);
        $score = $this->scoreFromFactors($factors);

        $record = $this->repository->upsertHealthScore($company, $score, $factors);

        if ($actor !== null) {
            $this->security->recordAudit(
                action: 'platform.health.calculated',
                user: $actor,
                auditable: $company,
                newValues: [
                    'score' => $score,
                    'risk_level' => HealthRiskLevel::fromScore($score)->value,
                ],
                companyId: $company->id,
            );
        }

        return new CompanyHealthDTO(
            companyId: $company->id,
            score: $record->score,
            riskLevel: $record->risk_level->value,
            factors: $record->factors ?? [],
            calculatedAt: $record->calculated_at->toIso8601String(),
        );
    }

    /**
     * @param  array<string, mixed>  $factors
     */
    protected function scoreFromFactors(array $factors): int
    {
        $score = 50;

        $companyStatus = $factors['company_status'] ?? null;
        $subscriptionStatus = $factors['subscription_status'] ?? null;

        if ($companyStatus === Company::STATUS_ACTIVE) {
            $score += 10;
        } elseif ($companyStatus === Company::STATUS_SUSPENDED) {
            $score -= 30;
        } elseif ($companyStatus === Company::STATUS_CANCELLED) {
            $score -= 40;
        }

        if ($subscriptionStatus === 'active') {
            $score += 15;
        } elseif ($subscriptionStatus === 'trial') {
            $score += 8;
        } elseif ($subscriptionStatus === 'past_due' || ($factors['past_due'] ?? false)) {
            $score -= 25;
        } elseif ($subscriptionStatus === 'cancelled') {
            $score -= 20;
        }

        $users = (int) ($factors['users_count'] ?? 0);
        $score += min(10, $users * 2);

        $properties = (int) ($factors['properties_count'] ?? 0);
        $score += min(10, (int) floor($properties / 10));

        $visits = (int) ($factors['visits_last_30d'] ?? 0);
        $score += min(15, $visits);

        $onboarding = (int) ($factors['onboarding_percent'] ?? 0);
        if ($onboarding >= 80) {
            $score += 10;
        } elseif ($onboarding >= 40) {
            $score += 5;
        } else {
            $score -= 5;
        }

        $lastLogin = $factors['last_login_at'] ?? null;
        if ($lastLogin) {
            $days = Carbon::parse($lastLogin)->diffInDays(now());
            if ($days <= 7) {
                $score += 10;
            } elseif ($days <= 30) {
                $score += 5;
            } else {
                $score -= 10;
            }
        } else {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }
}
