<?php

namespace App\Domains\SaasGrowth\Listeners;

use App\Domains\Onboarding\Events\OnboardingCompanyCompleted;
use App\Domains\Onboarding\Events\OnboardingCompleted;
use App\Domains\Onboarding\Events\OnboardingCustomerCreated;
use App\Domains\Onboarding\Events\OnboardingDealCreated;
use App\Domains\Onboarding\Events\OnboardingStarted;
use App\Domains\Onboarding\Events\OnboardingTeamCompleted;
use App\Domains\SaasGrowth\Enums\TrialMilestone;
use App\Domains\SaasGrowth\Services\TrialIntelligenceService;

class SyncSaasTrialMilestones
{
    public function __construct(
        protected TrialIntelligenceService $trials,
    ) {}

    public function handleStarted(OnboardingStarted $event): void
    {
        $this->trials->complete($event->company, TrialMilestone::CompanyCreated);
    }

    public function handleCompany(OnboardingCompanyCompleted $event): void
    {
        $this->trials->complete($event->company, TrialMilestone::CompanyCreated);
    }

    public function handleTeam(OnboardingTeamCompleted $event): void
    {
        $this->trials->complete($event->company, TrialMilestone::TeamCreated);
    }

    public function handleCustomer(OnboardingCustomerCreated $event): void
    {
        $this->trials->complete($event->company, TrialMilestone::CustomerCreated);
    }

    public function handleDeal(OnboardingDealCreated $event): void
    {
        $this->trials->complete($event->company, TrialMilestone::DealCreated);
        $this->trials->complete($event->company, TrialMilestone::FirstSale);
    }

    public function handleActivated(OnboardingCompleted $event): void
    {
        $this->trials->complete($event->company, TrialMilestone::Activated);
    }
}
