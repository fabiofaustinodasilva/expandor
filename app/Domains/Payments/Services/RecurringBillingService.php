<?php

namespace App\Domains\Payments\Services;

use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecurringBillingService
{
    public function __construct(
        protected InvoiceService $invoices,
        protected BillingDelinquencyPolicy $delinquency,
    ) {}

    /**
     * Gera faturas abertas para assinaturas ativas vencidas (idempotente por período).
     */
    public function generateDueInvoices(): int
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
                    if ($this->ensureOpenInvoice($subscription) !== null) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function ensureOpenInvoice(Subscription $subscription): ?Invoice
    {
        $dueAt = $subscription->next_billing_at ?? now();
        $periodKey = $dueAt->format('Y-m');

        $existing = Invoice::query()
            ->withoutGlobalScopes()
            ->where('subscription_id', $subscription->id)
            ->where('billing_period_key', $periodKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($subscription, $dueAt, $periodKey) {
            $locked = Subscription::query()
                ->withoutGlobalScopes()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return null;
            }

            $again = Invoice::query()
                ->withoutGlobalScopes()
                ->where('subscription_id', $locked->id)
                ->where('billing_period_key', $periodKey)
                ->first();

            if ($again !== null) {
                return $again;
            }

            $amount = round((float) ($locked->plan?->price ?? 0), 2);
            if ($amount <= 0) {
                return null;
            }

            $invoice = $this->invoices->create([
                'company_id' => $locked->company_id,
                'subscription_id' => $locked->id,
                'plan_id' => $locked->plan_id,
                'number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'billing_period_key' => $periodKey,
                'status' => InvoiceStatus::Open,
                'amount_due' => $amount,
                'amount_paid' => 0,
                'currency' => config('payments.currency', 'BRL'),
                'period_start' => $dueAt->copy()->startOfMonth(),
                'period_end' => $dueAt->copy()->endOfMonth(),
                'due_at' => $dueAt->copy()->startOfDay(),
                'gateway' => $locked->gateway ?: config('payments.default'),
                'metadata' => [
                    'source' => 'recurring_billing',
                    'period_key' => $periodKey,
                ],
            ]);

            // next_billing_at avança só após quitação (evita faturas duplicadas no mesmo ciclo).

            return $invoice;
        });
    }

    public function markOverdueInvoices(): int
    {
        $count = 0;

        Invoice::query()
            ->withoutGlobalScopes()
            ->where('status', InvoiceStatus::Open)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now()->startOfDay())
            ->chunkById(100, function ($invoices) use (&$count): void {
                foreach ($invoices as $invoice) {
                    if ($this->delinquency->shouldSuspend($invoice) || ! $this->delinquency->isWithinGrace($invoice)) {
                        // Within grace after due: still Open until suspend job; mark overdue when past due day.
                        if ($invoice->due_at !== null && now()->startOfDay()->gt($invoice->due_at->copy()->startOfDay())) {
                            $invoice->forceFill(['status' => InvoiceStatus::Overdue])->save();
                            $count++;
                        }
                    }
                }
            });

        return $count;
    }
}
