<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Integrations\Services\MapIntegrationResolver;
use App\Domains\Platform\Models\SubscriptionEvent;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PlatformSubscriptionService
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function current(Company $company): ?Subscription
    {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with('plan')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, SubscriptionEvent>
     */
    public function history(Company $company, int $limit = 50): Collection
    {
        return SubscriptionEvent::query()
            ->where('company_id', $company->id)
            ->with('actor')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function renewTrial(Company $company, User $actor, int $days): Subscription
    {
        $subscription = $this->requireSubscription($company);
        $days = max(1, $days);

        $oldTrial = $subscription->trial_ends_at;
        $base = $oldTrial && $oldTrial->isFuture() ? $oldTrial->copy() : now();
        $newTrialEnd = $base->copy()->addDays($days);

        $subscription->forceFill([
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => $newTrialEnd,
            'cancelled_at' => null,
            'next_billing_at' => $newTrialEnd->copy(),
        ])->save();

        $this->record($company, $subscription, $actor, 'trial.renewed', [
            'days' => $days,
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
        ], ['trial_ends_at' => $oldTrial?->toIso8601String()]);

        return $subscription->fresh(['plan']);
    }

    public function changePlan(Company $company, User $actor, int $planId): Subscription
    {
        $subscription = $this->requireSubscription($company);
        $plan = Plan::query()->where('id', $planId)->where('status', Plan::STATUS_ACTIVE)->firstOrFail();
        $oldPlanId = $subscription->plan_id;

        $subscription->forceFill(['plan_id' => $plan->id])->save();

        app(MapIntegrationResolver::class)->forget($company);

        $this->record($company, $subscription, $actor, 'plan.changed', [
            'plan_id' => $plan->id,
            'plan_slug' => $plan->slug,
        ], ['plan_id' => $oldPlanId]);

        return $subscription->fresh(['plan']);
    }

    public function changeDueDates(
        Company $company,
        User $actor,
        ?string $endsAt,
        ?string $nextBillingAt,
        ?string $trialEndsAt = null,
    ): Subscription {
        $subscription = $this->requireSubscription($company);
        $old = [
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'next_billing_at' => $subscription->next_billing_at?->toIso8601String(),
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
        ];

        $payload = [];
        if ($endsAt !== null && $endsAt !== '') {
            $payload['ends_at'] = $endsAt;
        }
        if ($nextBillingAt !== null && $nextBillingAt !== '') {
            $payload['next_billing_at'] = $nextBillingAt;
        }
        if ($trialEndsAt !== null && $trialEndsAt !== '') {
            $payload['trial_ends_at'] = $trialEndsAt;
        }

        if ($payload === []) {
            throw ValidationException::withMessages([
                'ends_at' => ['Informe ao menos uma data para atualizar.'],
            ]);
        }

        $subscription->forceFill($payload)->save();

        $this->record($company, $subscription, $actor, 'dates.updated', [
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'next_billing_at' => $subscription->next_billing_at?->toIso8601String(),
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
        ], $old);

        return $subscription->fresh(['plan']);
    }

    public function cancel(Company $company, User $actor, ?string $reason = null): Subscription
    {
        $subscription = $this->requireSubscription($company);
        $old = $subscription->status;

        $subscription->forceFill([
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'ends_at' => $subscription->ends_at ?? now(),
        ])->save();

        $this->record($company, $subscription, $actor, 'cancelled', [
            'reason' => $reason,
        ], ['status' => $old]);

        return $subscription->fresh(['plan']);
    }

    public function reactivate(Company $company, User $actor): Subscription
    {
        $subscription = $this->requireSubscription($company);
        $old = $subscription->status;

        $subscription->forceFill([
            'status' => Subscription::STATUS_ACTIVE,
            'cancelled_at' => null,
            'starts_at' => $subscription->starts_at ?? now(),
            'next_billing_at' => $subscription->next_billing_at ?? now()->addMonth(),
            'trial_ends_at' => null,
        ])->save();

        $this->record($company, $subscription, $actor, 'reactivated', [
            'status' => Subscription::STATUS_ACTIVE,
        ], ['status' => $old]);

        return $subscription->fresh(['plan']);
    }

    protected function requireSubscription(Company $company): Subscription
    {
        if ($company->isSystem()) {
            throw ValidationException::withMessages([
                'company' => ['Empresa sistema não possui assinatura comercial.'],
            ]);
        }

        $subscription = $this->current($company);
        if ($subscription === null) {
            throw ValidationException::withMessages([
                'company' => ['Empresa sem assinatura.'],
            ]);
        }

        return $subscription;
    }

    /**
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>|null  $oldValues
     */
    protected function record(
        Company $company,
        Subscription $subscription,
        User $actor,
        string $event,
        array $newValues,
        ?array $oldValues = null,
    ): void {
        SubscriptionEvent::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'actor_id' => $actor->id,
            'event' => $event,
            'payload' => [
                'old' => $oldValues,
                'new' => $newValues,
            ],
        ]);

        $this->security->recordAudit(
            action: 'platform.subscription.'.$event,
            user: $actor,
            auditable: $subscription,
            oldValues: $oldValues,
            newValues: $newValues,
            companyId: $company->id,
        );
    }
}
