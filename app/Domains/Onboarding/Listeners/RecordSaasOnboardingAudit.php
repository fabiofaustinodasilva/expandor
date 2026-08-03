<?php

namespace App\Domains\Onboarding\Listeners;

use App\Domains\Onboarding\Events\OnboardingCompanyCompleted;
use App\Domains\Onboarding\Events\OnboardingCompleted;
use App\Domains\Onboarding\Events\OnboardingCustomerCreated;
use App\Domains\Onboarding\Events\OnboardingStarted;
use App\Domains\Onboarding\Events\OnboardingTeamCompleted;
use App\Domains\Security\Services\SecurityService;

class RecordSaasOnboardingAudit
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function handleStarted(OnboardingStarted $event): void
    {
        $this->security->recordAudit(
            action: 'onboarding.started',
            user: $event->actor,
            auditable: $event->company,
            newValues: [
                'onboarding_status' => $event->company->onboarding_status,
                'onboarding_step' => $event->company->onboarding_step,
            ],
            companyId: $event->company->id,
        );
    }

    public function handleCompanyCompleted(OnboardingCompanyCompleted $event): void
    {
        $this->security->recordAudit(
            action: 'onboarding.company_completed',
            user: $event->actor,
            auditable: $event->company,
            newValues: [
                'onboarding_step' => $event->company->onboarding_step,
                'name' => $event->company->name,
            ],
            companyId: $event->company->id,
        );
    }

    public function handleTeamCompleted(OnboardingTeamCompleted $event): void
    {
        $this->security->recordAudit(
            action: 'onboarding.team_completed',
            user: $event->actor,
            auditable: $event->company,
            newValues: $event->meta,
            companyId: $event->company->id,
        );
    }

    public function handleCustomerCreated(OnboardingCustomerCreated $event): void
    {
        $this->security->recordAudit(
            action: 'onboarding.customer_created',
            user: $event->actor,
            auditable: $event->company,
            newValues: $event->meta,
            companyId: $event->company->id,
        );
    }

    public function handleCompleted(OnboardingCompleted $event): void
    {
        $this->security->recordAudit(
            action: 'onboarding.completed',
            user: $event->actor,
            auditable: $event->company,
            newValues: [
                'onboarding_status' => $event->company->onboarding_status,
                'onboarding_completed_at' => $event->company->onboarding_completed_at?->toIso8601String(),
            ],
            companyId: $event->company->id,
        );
    }
}
