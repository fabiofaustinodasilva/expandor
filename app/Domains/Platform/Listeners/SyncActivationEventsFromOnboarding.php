<?php

namespace App\Domains\Platform\Listeners;

use App\Domains\Onboarding\Events\OnboardingCompleted;
use App\Domains\Onboarding\Events\OnboardingCustomerCreated;
use App\Domains\Onboarding\Events\OnboardingDealCreated;
use App\Domains\Onboarding\Events\OnboardingStarted;
use App\Domains\Platform\Services\ActivationEventRecorder;

class SyncActivationEventsFromOnboarding
{
    public function __construct(
        protected ActivationEventRecorder $events,
    ) {}

    public function handleStarted(OnboardingStarted $event): void
    {
        $this->events->started($event->company, $event->actor);
    }

    public function handleCustomer(OnboardingCustomerCreated $event): void
    {
        $this->events->firstCustomer($event->company, $event->actor, $event->meta);
    }

    public function handleDeal(OnboardingDealCreated $event): void
    {
        $this->events->firstDeal($event->company, $event->actor, $event->meta);
    }

    public function handleCompleted(OnboardingCompleted $event): void
    {
        $this->events->completed($event->company, $event->actor);
    }
}
