<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Enums\OnboardingRunStatus;
use App\Domains\Onboarding\Enums\OnboardingStepStatus;
use App\Domains\Onboarding\Repositories\OnboardingRepository;
use App\Domains\Onboarding\Services\ChecklistService;

class FinishOnboardingAction
{
    public function __construct(
        protected OnboardingRepository $repository,
        protected ChecklistService $checklist,
        protected InitializeOnboardingAction $initialize,
        protected CompleteStepAction $completeStep,
    ) {}

    public function execute(Company $company, ?User $actor = null): void
    {
        $this->initialize->execute($company);

        foreach ($this->repository->progressForCompany($company) as $progress) {
            if (in_array($progress->status, [OnboardingStepStatus::Completed, OnboardingStepStatus::Skipped], true)) {
                continue;
            }

            $key = $progress->step?->key;
            if ($key !== null) {
                $this->completeStep->execute($company, $key, $actor, ['auto_finished' => true]);
            }
        }

        $run = $this->repository->runForCompany($company);
        $run?->forceFill([
            'status' => OnboardingRunStatus::Completed,
            'percent' => 100,
            'finished_at' => now(),
            'finished_by' => $actor?->id,
        ])->save();

        $this->checklist->refreshRunPercent($company);
    }
}
