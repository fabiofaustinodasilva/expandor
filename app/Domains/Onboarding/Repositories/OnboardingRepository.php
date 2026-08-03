<?php

namespace App\Domains\Onboarding\Repositories;

use App\Domains\Company\Models\Company;
use App\Domains\Onboarding\Enums\OnboardingRunStatus;
use App\Domains\Onboarding\Enums\OnboardingStepStatus;
use App\Domains\Onboarding\Enums\TourStatus;
use App\Domains\Onboarding\Models\OnboardingProgress;
use App\Domains\Onboarding\Models\OnboardingRun;
use App\Domains\Onboarding\Models\OnboardingStep;
use App\Domains\Onboarding\Models\SetupTemplate;
use Illuminate\Support\Collection;

class OnboardingRepository
{
    /**
     * @return Collection<int, OnboardingStep>
     */
    public function activeSteps(): Collection
    {
        return OnboardingStep::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function findStepByKey(string $key): ?OnboardingStep
    {
        return OnboardingStep::query()->where('key', $key)->where('is_active', true)->first();
    }

    public function runForCompany(Company $company): ?OnboardingRun
    {
        return OnboardingRun::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();
    }

    /**
     * @return Collection<int, OnboardingProgress>
     */
    public function progressForCompany(Company $company): Collection
    {
        return OnboardingProgress::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with('step')
            ->get()
            ->sortBy(fn (OnboardingProgress $progress) => $progress->step?->sort_order ?? 999)
            ->values();
    }

    public function progressForStep(Company $company, OnboardingStep $step): ?OnboardingProgress
    {
        return OnboardingProgress::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('onboarding_step_id', $step->id)
            ->first();
    }

    public function demoTemplate(): ?SetupTemplate
    {
        return SetupTemplate::query()
            ->where('key', 'demo_starter')
            ->where('is_active', true)
            ->first();
    }

    public function countRunsByStatus(OnboardingRunStatus $status): int
    {
        return OnboardingRun::query()
            ->withoutGlobalScopes()
            ->where('status', $status->value)
            ->count();
    }

    public function averageCompletionHours(): ?float
    {
        $rows = OnboardingRun::query()
            ->withoutGlobalScopes()
            ->where('status', OnboardingRunStatus::Completed->value)
            ->whereNotNull('finished_at')
            ->get(['created_at', 'finished_at']);

        if ($rows->isEmpty()) {
            return null;
        }

        $hours = $rows->avg(fn (OnboardingRun $run) => $run->created_at->diffInMinutes($run->finished_at) / 60);

        return round((float) $hours, 1);
    }

    public function stuckCompaniesCount(int $days = 7): int
    {
        return OnboardingRun::query()
            ->withoutGlobalScopes()
            ->where('status', OnboardingRunStatus::InProgress->value)
            ->where('updated_at', '<=', now()->subDays($days))
            ->count();
    }

    public function completionRate(): float
    {
        $total = OnboardingRun::query()->withoutGlobalScopes()->count();

        if ($total === 0) {
            return 0.0;
        }

        $completed = $this->countRunsByStatus(OnboardingRunStatus::Completed);

        return round(($completed / $total) * 100, 1);
    }
}
