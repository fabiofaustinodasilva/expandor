<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Events\OnboardingStarted;
use App\Domains\Onboarding\Services\SaasOnboardingService;

class StartSaasOnboardingAction
{
    public function __construct(
        protected SaasOnboardingService $saasOnboarding,
    ) {}

    public function execute(Company $company, ?User $actor = null): Company
    {
        $wasPending = $company->onboarding_status === Company::ONBOARDING_PENDING;
        $company = $this->saasOnboarding->markStarted($company);

        if ($wasPending) {
            OnboardingStarted::dispatch($company, $actor);
        }

        return $company;
    }
}
