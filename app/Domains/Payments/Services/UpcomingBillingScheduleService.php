<?php

namespace App\Domains\Payments\Services;

use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UpcomingBillingScheduleService
{
    public function __construct(
        protected RecurringBillingService $billing,
    ) {}

    /**
     * @return Collection<int, array{
     *     due_at: CarbonInterface,
     *     amount: float,
     *     status_key: string,
     *     status_label: string,
     *     is_real_invoice: bool,
     *     invoice_id: int|null,
     *     payable: bool,
     *     available_from: CarbonInterface|null
     * }>
     */
    public function upcomingCharges(Subscription $subscription, int $limit = 3, ?CarbonInterface $now = null): Collection
    {
        $now = Carbon::parse($now ?? now())->startOfDay();
        $anchor = $subscription->next_billing_at?->copy()->startOfDay();

        if ($anchor === null || ! $subscription->isEligibleForRecurringBilling()) {
            return collect();
        }

        $amount = $subscription->monthlyAmount();
        $invoicesByPeriod = Invoice::query()
            ->withoutGlobalScopes()
            ->where('subscription_id', $subscription->id)
            ->whereNotNull('billing_period_key')
            ->get()
            ->keyBy('billing_period_key');

        $rows = collect();
        $cursor = $anchor->copy();
        $generationDays = $this->billing->invoiceGenerationDays();

        while ($rows->count() < $limit) {
            $periodKey = $cursor->format('Y-m');
            $invoice = $invoicesByPeriod->get($periodKey);
            $availableFrom = $cursor->copy()->subDays($generationDays)->startOfDay();
            $withinWindow = $this->billing->isWithinGenerationWindow($cursor, $now);

            if ($invoice !== null) {
                $statusKey = match ($invoice->status) {
                    InvoiceStatus::Paid => 'paid',
                    InvoiceStatus::Overdue => 'overdue',
                    default => 'pending',
                };
                $statusLabel = $invoice->status->label();
                $payable = $invoice->status->isPayable();
            } elseif ($withinWindow) {
                $statusKey = 'next';
                $statusLabel = 'Próxima cobrança';
                $payable = false;
            } else {
                $statusKey = 'scheduled';
                $statusLabel = 'Programada';
                $payable = false;
            }

            $rows->push([
                'due_at' => $cursor->copy(),
                'amount' => $invoice !== null ? (float) $invoice->amount_due : $amount,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'is_real_invoice' => $invoice !== null,
                'invoice_id' => $invoice?->id,
                'payable' => $payable,
                'available_from' => $invoice === null && ! $withinWindow ? $availableFrom : null,
            ]);

            $cursor = ($subscription->billing_cycle === 'yearly')
                ? $cursor->copy()->addYear()
                : $cursor->copy()->addMonthNoOverflow();
        }

        return $rows;
    }

    /**
     * @return array{due_at: CarbonInterface, amount: float, available_from: CarbonInterface, within_window: bool}|null
     */
    public function nextChargePreview(Subscription $subscription, ?CarbonInterface $now = null): ?array
    {
        $dueAt = $subscription->next_billing_at?->copy()->startOfDay();
        if ($dueAt === null || ! $subscription->isEligibleForRecurringBilling()) {
            return null;
        }

        $now = Carbon::parse($now ?? now())->startOfDay();

        return [
            'due_at' => $dueAt,
            'amount' => $subscription->monthlyAmount(),
            'available_from' => $this->billing->invoiceAvailableFrom($dueAt),
            'within_window' => $this->billing->isWithinGenerationWindow($dueAt, $now),
        ];
    }
}
