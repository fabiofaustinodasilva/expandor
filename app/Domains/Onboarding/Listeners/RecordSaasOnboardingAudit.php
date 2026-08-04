<?php

namespace App\Domains\Onboarding\Listeners;

use App\Domains\Onboarding\Events\OnboardingBrandingCompleted;
use App\Domains\Onboarding\Events\OnboardingCompanyCompleted;
use App\Domains\Onboarding\Events\OnboardingCompleted;
use App\Domains\Onboarding\Events\OnboardingCustomerCreated;
use App\Domains\Onboarding\Events\OnboardingDealCreated;
use App\Domains\Onboarding\Events\OnboardingDismissed;
use App\Domains\Onboarding\Events\OnboardingStarted;
use App\Domains\Onboarding\Events\OnboardingStepSkipped;
use App\Domains\Onboarding\Events\OnboardingTeamCompleted;
use App\Domains\Security\Services\SecurityService;

class RecordSaasOnboardingAudit
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function handleStarted(OnboardingStarted $event): void
    {
        $this->audit('onboarding.started', $event->company, $event->actor, [
            'onboarding_status' => $event->company->onboarding_status,
            'onboarding_step' => $event->company->onboarding_step,
        ]);
    }

    public function handleCompanyCompleted(OnboardingCompanyCompleted $event): void
    {
        $this->audit('onboarding.company_completed', $event->company, $event->actor, [
            'onboarding_step' => $event->company->onboarding_step,
            'name' => $event->company->name,
        ]);
    }

    public function handleTeamCompleted(OnboardingTeamCompleted $event): void
    {
        $this->audit('onboarding.team_completed', $event->company, $event->actor, $event->meta);
    }

    public function handleCustomerCreated(OnboardingCustomerCreated $event): void
    {
        $this->audit('onboarding.customer_created', $event->company, $event->actor, $event->meta);
    }

    public function handleDealCreated(OnboardingDealCreated $event): void
    {
        $this->audit('onboarding.deal_created', $event->company, $event->actor, $event->meta);
    }

    public function handleBrandingCompleted(OnboardingBrandingCompleted $event): void
    {
        $this->audit('onboarding.branding_completed', $event->company, $event->actor, $event->meta);
    }

    public function handleStepSkipped(OnboardingStepSkipped $event): void
    {
        $this->audit('onboarding.step_skipped', $event->company, $event->actor, array_merge([
            'step' => $event->step,
        ], $event->meta));
    }

    public function handleDismissed(OnboardingDismissed $event): void
    {
        $this->audit('onboarding.dismissed', $event->company, $event->actor, $event->meta);
    }

    public function handleCompleted(OnboardingCompleted $event): void
    {
        $this->audit('onboarding.completed', $event->company, $event->actor, [
            'onboarding_status' => $event->company->onboarding_status,
            'onboarding_completed_at' => $event->company->onboarding_completed_at?->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function audit(string $action, $company, $actor, array $meta): void
    {
        $this->security->recordAudit(
            action: $action,
            user: $actor,
            auditable: $company,
            newValues: array_merge([
                'company_id' => $company->id,
                'user_id' => $actor?->id,
            ], $meta),
            companyId: $company->id,
        );
    }
}
