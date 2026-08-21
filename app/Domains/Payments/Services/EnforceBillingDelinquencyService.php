<?php

namespace App\Domains\Payments\Services;

use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Actions\SuspendSubscriptionAction;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Support\BillingSuspensionReasons;

class EnforceBillingDelinquencyService
{
    public function __construct(
        protected BillingDelinquencyPolicy $policy,
        protected SuspendSubscriptionAction $suspend,
        protected RecurringBillingService $recurring,
    ) {}

    public function run(): int
    {
        $this->recurring->generateDueInvoices();
        $this->recurring->markOverdueInvoices();

        $suspended = 0;

        Invoice::query()
            ->withoutGlobalScopes()
            ->whereIn('status', [InvoiceStatus::Open->value, InvoiceStatus::Overdue->value])
            ->whereNotNull('due_at')
            ->whereNull('paid_at')
            ->with('subscription')
            ->chunkById(50, function ($invoices) use (&$suspended): void {
                foreach ($invoices as $invoice) {
                    if (! $this->policy->shouldSuspend($invoice)) {
                        continue;
                    }

                    $subscription = $invoice->subscription
                        ?? Subscription::query()->withoutGlobalScopes()->find($invoice->subscription_id);

                    if ($subscription === null) {
                        continue;
                    }

                    if ($subscription->status === Subscription::STATUS_CANCELLED) {
                        continue;
                    }

                    $this->suspend->execute($subscription, BillingSuspensionReasons::BILLING_PAST_DUE);
                    $invoice->forceFill(['status' => InvoiceStatus::Overdue])->save();
                    $suspended++;
                }
            });

        return $suspended;
    }
}
