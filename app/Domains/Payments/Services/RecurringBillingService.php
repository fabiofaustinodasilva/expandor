<?php

namespace App\Domains\Payments\Services;

use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecurringBillingService
{
    public function __construct(
        protected InvoiceService $invoices,
        protected BillingDelinquencyPolicy $delinquency,
    ) {}

    public function invoiceGenerationDays(): int
    {
        return max(0, (int) config('payments.invoice_generation_days', 10));
    }

    /**
     * A fatura pode ser gerada quando hoje >= vencimento - N dias.
     */
    public function isWithinGenerationWindow(CarbonInterface $dueAt, ?CarbonInterface $now = null): bool
    {
        $now = Carbon::parse($now ?? now())->startOfDay();
        $due = Carbon::parse($dueAt)->startOfDay();
        $windowEnd = $now->copy()->addDays($this->invoiceGenerationDays())->endOfDay();

        return $due->lte($windowEnd);
    }

    public function invoiceAvailableFrom(CarbonInterface $dueAt): CarbonInterface
    {
        return Carbon::parse($dueAt)->startOfDay()->subDays($this->invoiceGenerationDays());
    }

    /**
     * Gera faturas abertas para assinaturas elegíveis dentro da janela de antecedência (idempotente por período).
     */
    public function generateDueInvoices(): int
    {
        $count = 0;
        $windowEnd = now()->addDays($this->invoiceGenerationDays())->endOfDay();

        Subscription::query()
            ->withoutGlobalScopes()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNull('cancelled_at')
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<=', $windowEnd)
            ->with('plan')
            ->chunkById(50, function ($subscriptions) use (&$count): void {
                foreach ($subscriptions as $subscription) {
                    $periodKey = ($subscription->next_billing_at ?? now())->format('Y-m');
                    $existed = Invoice::query()
                        ->withoutGlobalScopes()
                        ->where('subscription_id', $subscription->id)
                        ->where('billing_period_key', $periodKey)
                        ->exists();

                    $invoice = $this->ensureOpenInvoice($subscription);
                    if ($invoice !== null && ! $existed) {
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

            if ($locked === null || ! $locked->isEligibleForRecurringBilling()) {
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

            $amount = round((float) $locked->monthlyAmount(), 2);
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
                    'contracted_amount' => $amount,
                ],
            ]);

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
                    $invoice->forceFill(['status' => InvoiceStatus::Overdue])->save();
                    $count++;
                }
            });

        return $count;
    }
}
