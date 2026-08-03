<?php

namespace App\Domains\Payments\Repositories;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Enums\WebhookEventStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentMethod;
use App\Domains\Payments\Models\WebhookEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaymentRepository
{
    public function findActivePlan(int $planId): ?Plan
    {
        return Plan::query()
            ->where('id', $planId)
            ->where('status', Plan::STATUS_ACTIVE)
            ->first();
    }

    public function findCheckoutByUuid(string $uuid): ?CheckoutSession
    {
        return CheckoutSession::query()->where('uuid', $uuid)->first();
    }

    public function findCheckoutByGatewaySession(string $gateway, string $gatewaySessionId): ?CheckoutSession
    {
        return CheckoutSession::query()
            ->where('gateway', $gateway)
            ->where('gateway_session_id', $gatewaySessionId)
            ->first();
    }

    public function findCheckoutForWebhook(string $gateway, ?string $gatewayCheckoutId): ?CheckoutSession
    {
        if ($gatewayCheckoutId === null || $gatewayCheckoutId === '') {
            return null;
        }

        return $this->findCheckoutByUuid($gatewayCheckoutId)
            ?? $this->findCheckoutByGatewaySession($gateway, $gatewayCheckoutId);
    }

    public function findCustomerByGateway(string $gateway, string $gatewayCustomerId): ?Customer
    {
        return Customer::query()
            ->withoutGlobalScopes()
            ->where('gateway', $gateway)
            ->where('gateway_customer_id', $gatewayCustomerId)
            ->first();
    }

    public function findWebhook(string $gateway, string $eventId): ?WebhookEvent
    {
        return WebhookEvent::query()
            ->where('gateway', $gateway)
            ->where('event_id', $eventId)
            ->first();
    }

    public function findPaymentByGateway(string $gateway, string $gatewayPaymentId): ?Payment
    {
        return Payment::query()
            ->withoutGlobalScopes()
            ->where('gateway', $gateway)
            ->where('gateway_payment_id', $gatewayPaymentId)
            ->first();
    }

    public function activeSubscription(Company $company): ?Subscription
    {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIAL,
                Subscription::STATUS_PAST_DUE,
            ])
            ->with('plan')
            ->latest('id')
            ->first();
    }

    public function customerForCompany(Company $company): ?Customer
    {
        return Customer::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, Payment>
     */
    public function paymentsForCompany(Company $company, int $limit = 50): Collection
    {
        return Payment::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function invoicesForCompany(Company $company, int $limit = 50): Collection
    {
        return Invoice::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    public function paymentMethodsForCompany(Company $company): Collection
    {
        return PaymentMethod::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->orderByDesc('is_default')
            ->get();
    }

    public function paginateClientPlans(int $perPage = 20): LengthAwarePaginator
    {
        return Plan::query()
            ->where('status', Plan::STATUS_ACTIVE)
            ->where('price', '>', 0)
            ->orderBy('display_order')
            ->orderBy('price')
            ->paginate($perPage);
    }

    public function countPaidCheckouts(): int
    {
        return CheckoutSession::query()
            ->whereIn('status', [CheckoutStatus::Paid->value, CheckoutStatus::Provisioned->value])
            ->count();
    }

    public function sumPaidPaymentsThisMonth(): float
    {
        return (float) Payment::query()
            ->withoutGlobalScopes()
            ->where('status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
    }

    public function sumPaidPaymentsThisYear(): float
    {
        return (float) Payment::query()
            ->withoutGlobalScopes()
            ->where('status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [now()->startOfYear(), now()->endOfYear()])
            ->sum('amount');
    }

    public function countInvoicesByStatus(InvoiceStatus $status): int
    {
        return Invoice::query()
            ->withoutGlobalScopes()
            ->where('status', $status->value)
            ->count();
    }

    public function countWebhookProcessed(): int
    {
        return WebhookEvent::query()
            ->where('status', WebhookEventStatus::Processed->value)
            ->count();
    }
}
