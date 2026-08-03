<?php

namespace App\Domains\Payments\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Repositories\PaymentRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingAutomationService
{
    public function __construct(
        protected PaymentRepository $repository,
        protected SubscriptionService $subscriptions,
        protected PaymentService $payments,
    ) {}

    public function renewDueSubscriptions(): int
    {
        $count = 0;

        Subscription::query()
            ->withoutGlobalScopes()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<=', now())
            ->with('plan')
            ->chunkById(50, function ($subscriptions) use (&$count): void {
                foreach ($subscriptions as $subscription) {
                    $this->renewSubscription($subscription);
                    $count++;
                }
            });

        return $count;
    }

    public function renewSubscription(Subscription $subscription): Payment
    {
        return DB::transaction(function () use ($subscription) {
            $amount = (float) ($subscription->plan?->price ?? 0);

            $payment = $this->payments->create([
                'company_id' => $subscription->company_id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'currency' => config('payments.currency', 'BRL'),
                'status' => PaymentStatus::Paid,
                'method' => 'renewal',
                'gateway' => $subscription->gateway ?? config('payments.default'),
                'gateway_payment_id' => 'renewal_'.$subscription->id.'_'.now()->timestamp,
                'paid_at' => now(),
                'raw' => ['source' => 'renewal'],
            ]);

            $this->payments->markPaid($payment);

            $subscription->forceFill([
                'next_billing_at' => ($subscription->billing_cycle === 'yearly')
                    ? now()->addYear()
                    : now()->addMonth(),
                'status' => Subscription::STATUS_ACTIVE,
            ])->save();

            return $payment->fresh();
        });
    }

    public function expireTrials(): int
    {
        $count = 0;

        Subscription::query()
            ->withoutGlobalScopes()
            ->where('status', Subscription::STATUS_TRIAL)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->chunkById(50, function ($subscriptions) use (&$count): void {
                foreach ($subscriptions as $subscription) {
                    $this->subscriptions->suspend($subscription);
                    $count++;
                }
            });

        return $count;
    }

    public function retryFailedPayments(): int
    {
        $count = 0;

        Payment::query()
            ->withoutGlobalScopes()
            ->where('status', PaymentStatus::Failed)
            ->where('created_at', '>=', now()->subDays(7))
            ->chunkById(50, function ($payments) use (&$count): void {
                foreach ($payments as $payment) {
                    $payment->forceFill([
                        'status' => PaymentStatus::Pending,
                        'failure_reason' => null,
                    ])->save();
                    $count++;
                }
            });

        return $count;
    }

    public function syncSubscriptions(): int
    {
        $count = 0;

        CheckoutSession::query()
            ->where('status', CheckoutStatus::Paid)
            ->whereNull('provisioned_at')
            ->chunkById(20, function ($sessions) use (&$count): void {
                foreach ($sessions as $session) {
                    // Paid but not provisioned — left for webhook reprocessing.
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    public function platformRevenueMetrics(): array
    {
        $clientIds = Company::query()->where('is_system', false)->pluck('id');

        $activeSubs = Subscription::query()
            ->withoutGlobalScopes()
            ->whereIn('company_id', $clientIds)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->with('plan')
            ->get();

        $mrr = $activeSubs->sum(function (Subscription $subscription) {
            $price = (float) ($subscription->plan?->price ?? 0);

            return $subscription->billing_cycle === 'yearly' ? round($price / 12, 2) : $price;
        });

        $byPlan = $activeSubs
            ->groupBy(fn (Subscription $s) => $s->plan?->slug ?? 'unknown')
            ->map(fn (Collection $group) => [
                'count' => $group->count(),
                'mrr' => $group->sum(function (Subscription $subscription) {
                    $price = (float) ($subscription->plan?->price ?? 0);

                    return $subscription->billing_cycle === 'yearly' ? round($price / 12, 2) : $price;
                }),
            ]);

        $revenueByPlan = Plan::query()
            ->where('status', Plan::STATUS_ACTIVE)
            ->get()
            ->mapWithKeys(function (Plan $plan) use ($byPlan) {
                $stats = $byPlan->get($plan->slug, ['count' => 0, 'mrr' => 0]);

                return [$plan->slug => $stats];
            });

        return [
            'mrr' => round((float) $mrr, 2),
            'arr' => round((float) $mrr * 12, 2),
            'monthly_revenue' => $this->repository->sumPaidPaymentsThisMonth(),
            'yearly_revenue' => $this->repository->sumPaidPaymentsThisYear(),
            'active_clients' => Company::query()
                ->where('is_system', false)
                ->where('status', Company::STATUS_ACTIVE)
                ->count(),
            'trial_clients' => Subscription::query()
                ->withoutGlobalScopes()
                ->where('status', Subscription::STATUS_TRIAL)
                ->whereIn('company_id', $clientIds)
                ->count(),
            'suspended_clients' => Company::query()
                ->where('is_system', false)
                ->where('status', Company::STATUS_SUSPENDED)
                ->count(),
            'past_due_clients' => Subscription::query()
                ->withoutGlobalScopes()
                ->where('status', Subscription::STATUS_PAST_DUE)
                ->whereIn('company_id', $clientIds)
                ->count(),
            'clients_by_plan' => $revenueByPlan,
            'revenue_by_plan' => $revenueByPlan,
        ];
    }
}
