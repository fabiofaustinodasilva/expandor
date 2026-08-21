<?php

namespace App\Domains\Payments\Services;

use App\Domains\Company\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class BillingFidelityService
{
    public function defaultMinimumTermMonths(): int
    {
        return max(1, (int) config('payments.fidelity.minimum_term_months', 6));
    }

    /**
     * Aplica fidelidade somente em assinaturas novas (campos ainda nulos).
     */
    public function applyToNewSubscription(Subscription $subscription, ?CarbonInterface $startedAt = null): Subscription
    {
        if ($subscription->contract_started_at !== null || $subscription->minimum_term_ends_at !== null) {
            return $subscription;
        }

        $started = Carbon::parse($startedAt ?? $subscription->starts_at ?? now());
        $months = $this->defaultMinimumTermMonths();

        $subscription->forceFill([
            'contract_started_at' => $started,
            'minimum_term_months' => $months,
            'minimum_term_ends_at' => $started->copy()->addMonthsNoOverflow($months),
        ])->save();

        return $subscription->fresh();
    }

    /**
     * @return array{has_term:bool, months:int|null, started_at:?CarbonInterface, ends_at:?CarbonInterface, completed_months:int, remaining_months:int, inside_term:bool, progress_label:string}
     */
    public function progress(Subscription $subscription, ?CarbonInterface $now = null): array
    {
        $now = Carbon::parse($now ?? now());
        $started = $subscription->contract_started_at;
        $ends = $subscription->minimum_term_ends_at;
        $months = $subscription->minimum_term_months;

        if ($started === null || $ends === null || $months === null) {
            return [
                'has_term' => false,
                'months' => null,
                'started_at' => null,
                'ends_at' => null,
                'completed_months' => 0,
                'remaining_months' => 0,
                'inside_term' => false,
                'progress_label' => 'Sem fidelidade registrada (legado ou isento)',
            ];
        }

        $completed = min(
            (int) $months,
            max(0, (int) $started->diffInMonths($now, false))
        );
        if ($now->gte($ends)) {
            $completed = (int) $months;
        }

        $remaining = max(0, (int) $months - $completed);
        $inside = $now->lt($ends);

        return [
            'has_term' => true,
            'months' => (int) $months,
            'started_at' => $started,
            'ends_at' => $ends,
            'completed_months' => $completed,
            'remaining_months' => $remaining,
            'inside_term' => $inside,
            'progress_label' => $completed.' de '.$months.' meses',
        ];
    }
}
