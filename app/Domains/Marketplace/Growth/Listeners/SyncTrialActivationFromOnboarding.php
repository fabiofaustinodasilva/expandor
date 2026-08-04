<?php

namespace App\Domains\Marketplace\Growth\Listeners;

use App\Domains\Marketplace\Growth\Services\TrialGrowthIntelligenceService;
use App\Domains\Onboarding\Events\OnboardingCompleted;

class SyncTrialActivationFromOnboarding
{
    public function __construct(
        protected TrialGrowthIntelligenceService $trialGrowth,
    ) {}

    public function handle(OnboardingCompleted $event): void
    {
        $this->trialGrowth->markActivationCompleted($event->company);
    }
}
