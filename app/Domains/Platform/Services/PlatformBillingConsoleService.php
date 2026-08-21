<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Services\BillingDelinquencyPolicy;
use App\Domains\Payments\Support\BillingSuspensionReasons;
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
     *     financeRows: Collection<int, array<string, mixed>>,
     *     saasMetrics: array<string, mixed>,
     *     filters: array{subscription_status:?string, payment_status:?string, financial_status:?string},
     *     counts: array{trial:int, active:int, suspended:int, cancelled:int, past_due:int, pending_checkouts:int, paid_this_month:float|int|string}
     * }
     */
    public function dashboard(?string $subscriptionStatus = null, ?string $paymentStatus = null, ?string $financialStatus = null): array
    {
        return [
            'payments' => $this->paginatePayments($paymentStatus),
            'subscriptions' => $this->paginateSubscriptions($subscriptionStatus),
            'pendingCheckouts' => $this->pendingCheckouts(),
            'upcomingRenewals' => $this->upcomingRenewals(),
            'financeRows' => $this->financeRows($financialStatus),
            'saasMetrics' => $this->saasMetrics(),
            'filters' => [
                'subscription_status' => $subscriptionStatus,
                'payment_status' => $paymentStatus,
                'financial_status' => $financialStatus,
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

    /**
     * @return array<string, float|int>
     */
    public function saasMetrics(): array
    {
        $clientIds = Company::query()->where('is_system', false)->pluck('id');
        $policy = app(BillingDelinquencyPolicy::class);

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

        $openInvoices = Invoice::query()
            ->withoutGlobalScopes()
            ->whereIn('company_id', $clientIds)
            ->whereIn('status', [InvoiceStatus::Open->value, InvoiceStatus::Overdue->value])
            ->get();

        $inGrace = $openInvoices->filter(fn (Invoice $invoice) => $policy->isWithinGrace($invoice))->count();
        $pastGrace = $openInvoices->filter(fn (Invoice $invoice) => $policy->shouldSuspend($invoice))->count();

        return [
            'mrr' => round((float) $mrr, 2),
            'received_this_month' => (float) Payment::query()
                ->withoutGlobalScopes()
                ->where('status', PaymentStatus::Paid->value)
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
            'receivable' => round((float) $openInvoices->sum('amount_due'), 2),
            'delinquency' => round((float) $openInvoices->where('status', InvoiceStatus::Overdue)->sum('amount_due'), 2),
            'companies_current' => Company::query()->where('is_system', false)->where('status', Company::STATUS_ACTIVE)->count(),
            'companies_in_grace' => $inGrace,
            'companies_financially_suspended' => Company::query()
                ->where('is_system', false)
                ->where('status', Company::STATUS_SUSPENDED)
                ->where('suspension_reason', BillingSuspensionReasons::BILLING_PAST_DUE)
                ->count(),
            'invoices_past_grace' => $pastGrace,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function financeRows(?string $financialStatus = null, int $limit = 100): Collection
    {
        $policy = app(BillingDelinquencyPolicy::class);

        $subs = Subscription::query()
            ->withoutGlobalScopes()
            ->with(['plan', 'company'])
            ->whereHas('company', fn ($q) => $q->withoutGlobalScopes()->where('is_system', false))
            ->latest('id')
            ->limit($limit)
            ->get();

        return $subs->map(function (Subscription $subscription) use ($policy) {
            $company = $subscription->company;
            $open = Invoice::query()
                ->withoutGlobalScopes()
                ->where('subscription_id', $subscription->id)
                ->whereIn('status', [InvoiceStatus::Open->value, InvoiceStatus::Overdue->value])
                ->orderByDesc('due_at')
                ->first();

            $days = $open?->due_at ? $policy->daysPastDue($open->due_at) : 0;
            $status = 'em_dia';
            if ($company?->status === Company::STATUS_SUSPENDED && BillingSuspensionReasons::isFinancial($company->suspension_reason)) {
                $status = 'suspenso';
            } elseif ($open && $policy->shouldSuspend($open)) {
                $status = 'vencido';
            } elseif ($open && $policy->isWithinGrace($open) && $days > 0) {
                $status = 'tolerancia';
            } elseif ($open) {
                $status = 'pendente';
            }

            return [
                'company' => $company,
                'plan' => $subscription->plan,
                'monthly' => (float) ($subscription->plan?->price ?? 0),
                'next_due' => $open?->due_at ?? $subscription->next_billing_at,
                'financial_status' => $status,
                'days_past_due' => $days,
                'fidelity_ends' => $subscription->minimum_term_ends_at,
            ];
        })->when($financialStatus, function (Collection $rows) use ($financialStatus) {
            $map = [
                'em_dia' => 'em_dia',
                'pendente' => 'pendente',
                'tolerancia' => 'tolerancia',
                'vencido' => 'vencido',
                'suspenso' => 'suspenso',
            ];

            $wanted = $map[$financialStatus] ?? null;
            if ($wanted === null) {
                return $rows;
            }

            return $rows->filter(fn (array $row) => $row['financial_status'] === $wanted)->values();
        });
    }
}
