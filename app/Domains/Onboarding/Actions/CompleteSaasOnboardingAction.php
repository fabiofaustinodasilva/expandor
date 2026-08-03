<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Events\OnboardingCompleted;
use App\Domains\Onboarding\Services\SaasOnboardingService;

class CompleteSaasOnboardingAction
{
    public function __construct(
        protected SaasOnboardingService $saasOnboarding,
    ) {}

    public function execute(Company $company, ?User $actor = null): Company
    {
        if ($company->hasCompletedSaasOnboarding()) {
            return $company;
        }

        $company = $this->saasOnboarding->complete($company);
        OnboardingCompleted::dispatch($company, $actor);

        return $company;
    }
}
