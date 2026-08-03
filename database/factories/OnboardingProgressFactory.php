<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Onboarding\Enums\OnboardingStepStatus;
use App\Domains\Onboarding\Models\OnboardingProgress;
use App\Domains\Onboarding\Models\OnboardingStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OnboardingProgress> */
class OnboardingProgressFactory extends Factory
{
    protected $model = OnboardingProgress::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'onboarding_step_id' => OnboardingStep::factory(),
            'status' => OnboardingStepStatus::Pending,
            'percent' => 0,
            'metadata' => [],
        ];
    }
}
