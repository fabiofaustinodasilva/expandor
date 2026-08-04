<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Events\OnboardingDismissed;
use App\Domains\Onboarding\Services\SaasOnboardingService;

class DismissActivationCardAction
{
    public function __construct(
        protected SaasOnboardingService $saasOnboarding,
    ) {}

    public function execute(Company $company, User $user): void
    {
        $progress = $this->saasOnboarding->progress($company);
        $this->saasOnboarding->dismissActivationCard($company, $user);

        OnboardingDismissed::dispatch($company, $user, [
            'step' => $progress->step,
            'percent' => $progress->percent,
            'type' => 'activation_card',
        ]);
    }
}
