<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Events\OnboardingStepSkipped;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use Illuminate\Validation\ValidationException;

class SkipOnboardingStepAction
{
    public function __construct(
        protected SaasOnboardingService $saasOnboarding,
    ) {}

    public function execute(Company $company, string $step, ?User $actor = null): Company
    {
        $next = match ($step) {
            'team' => SaasOnboardingService::STEP_CUSTOMER,
            'customer' => SaasOnboardingService::STEP_SALES_SETUP,
            'deal', 'sales_setup' => SaasOnboardingService::STEP_BRANDING,
            'branding' => SaasOnboardingService::STEP_FINISH,
            default => throw ValidationException::withMessages([
                'step' => ['Etapa não pode ser pulada.'],
            ]),
        };

        $company = $this->saasOnboarding->advanceTo($company, $next);
        OnboardingStepSkipped::dispatch($company, $step, $actor, [
            'from_step' => $step,
            'to_step' => $next,
        ]);

        return $company;
    }
}
