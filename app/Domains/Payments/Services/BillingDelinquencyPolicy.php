<?php

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class BillingDelinquencyPolicy
{
    public function graceDays(): int
    {
        return max(0, (int) config('payments.delinquency.grace_days', 5));
    }

    public function graceEndsAt(CarbonInterface $dueAt): CarbonInterface
    {
        return Carbon::parse($dueAt)->copy()->startOfDay()->addDays($this->graceDays());
    }

    public function daysPastDue(CarbonInterface $dueAt, ?CarbonInterface $now = null): int
    {
        $now = Carbon::parse($now ?? now())->startOfDay();
        $due = Carbon::parse($dueAt)->startOfDay();

        return max(0, (int) $due->diffInDays($now, false));
    }

    public function isWithinGrace(Invoice $invoice, ?CarbonInterface $now = null): bool
    {
        if ($invoice->due_at === null || $invoice->paid_at !== null) {
            return false;
        }

        $now = Carbon::parse($now ?? now());

        return $now->lte($this->graceEndsAt($invoice->due_at)->endOfDay());
    }

    public function shouldSuspend(Invoice $invoice, ?CarbonInterface $now = null): bool
    {
        if (! $invoice->status->isPayable() || $invoice->due_at === null || $invoice->paid_at !== null) {
            return false;
        }

        $now = Carbon::parse($now ?? now());

        return $now->gt($this->graceEndsAt($invoice->due_at)->endOfDay());
    }
}
