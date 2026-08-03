<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Enums\OnboardingRunStatus;
use App\Domains\Onboarding\Enums\OnboardingStepStatus;
use App\Domains\Onboarding\Enums\TourStatus;
use App\Domains\Onboarding\Models\OnboardingProgress;
use App\Domains\Onboarding\Models\OnboardingRun;
use App\Domains\Onboarding\Models\OnboardingStep;
use App\Domains\Onboarding\Repositories\OnboardingRepository;

class InitializeOnboardingAction
{
    public function __construct(
        protected OnboardingRepository $repository,
    ) {}

    public function execute(Company $company): OnboardingRun
    {
        $run = $this->repository->runForCompany($company);

        if ($run === null) {
            $run = OnboardingRun::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'status' => OnboardingRunStatus::InProgress,
                'percent' => 0,
                'demo_generated' => false,
                'tour_status' => TourStatus::Pending,
            ]);
        }

        foreach ($this->repository->activeSteps() as $step) {
            OnboardingProgress::query()->withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'onboarding_step_id' => $step->id,
                ],
                [
                    'status' => OnboardingStepStatus::Pending,
                    'percent' => 0,
                ]
            );
        }

        return $run->fresh();
    }
}
