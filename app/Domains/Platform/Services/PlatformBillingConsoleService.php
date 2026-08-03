<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PlatformBillingConsoleService
{
    /**
     * @return array{
     *     payments: LengthAwarePaginator,
     *     subscriptions: LengthAwarePaginator,
     *     pendingCheckouts: Collection<int, CheckoutSession>,
     *     upcomingRenewals: Collection<int, Subscription>,
     *     filters: array{subscription_status:?string, payment_status:?string},
     *     counts: array{trial:int, active:int, suspended:int, cancelled:int, past_due:int, pending_checkouts:int, paid_this_month:float|int|string}
     * }
     */
    public function dashboard(?string $subscriptionStatus = null, ?string $paymentStatus = null): array
    {
        return [
            'payments' => $this->paginatePayments($paymentStatus),
            'subscriptions' => $this->paginateSubscriptions($subscriptionStatus),
            'pendingCheckouts' => $this->pendingCheckouts(),
            'upcomingRenewals' => $this->upcomingRenewals(),
            'filters' => [
                'subscription_status' => $subscriptionStatus,
                'payment_status' => $paymentStatus,
            ],
            'counts' => $this->counts(),
        ];
    }

    public function paginatePayments(?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Payment::query()
            ->withoutGlobalScopes()
            ->with(['company', 'subscription.plan'])
            ->latest('id');

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query->paginate($perPage, ['*'], 'payments_page')->withQueryString();
    }

    public function paginateSubscriptions(?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Subscription::query()
            ->withoutGlobalScopes()
            ->with(['plan', 'company'])
            ->latest('id');

        if ($status === 'suspended') {
            $query->whereHas('company', fn ($q) => $q->withoutGlobalScopes()->where('status', Company::STATUS_SUSPENDED));
        } elseif ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query->paginate($perPage, ['*'], 'subscriptions_page')->withQueryString();
    }

    /**
     * @return Collection<int, CheckoutSession>
     */
    public function pendingCheckouts(int $limit = 25): Collection
    {
        return CheckoutSession::query()
            ->with('plan')
            ->where('status', CheckoutStatus::Pending->value)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function upcomingRenewals(int $days = 14, int $limit = 25): Collection
    {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->with(['plan', 'company'])
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL, Subscription::STATUS_PAST_DUE])
            ->whereNotNull('next_billing_at')
            ->whereBetween('next_billing_at', [now(), now()->addDays($days)])
            ->orderBy('next_billing_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{trial:int, active:int, suspended:int, cancelled:int, past_due:int, pending_checkouts:int, paid_this_month:float|int|string}
     */
    public function counts(): array
    {
        $base = Subscription::query()->withoutGlobalScopes();

        return [
            'trial' => (clone $base)->where('status', Subscription::STATUS_TRIAL)->count(),
            'active' => (clone $base)->where('status', Subscription::STATUS_ACTIVE)->count(),
            'past_due' => (clone $base)->where('status', Subscription::STATUS_PAST_DUE)->count(),
            'cancelled' => (clone $base)->where('status', Subscription::STATUS_CANCELLED)->count(),
            'suspended' => Company::query()
                ->withoutGlobalScopes()
                ->where('is_system', false)
                ->where('status', Company::STATUS_SUSPENDED)
                ->count(),
            'pending_checkouts' => CheckoutSession::query()
                ->where('status', CheckoutStatus::Pending->value)
                ->count(),
            'paid_this_month' => Payment::query()
                ->withoutGlobalScopes()
                ->where('status', PaymentStatus::Paid->value)
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
        ];
    }
}
