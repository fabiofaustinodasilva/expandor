<?php

namespace App\Domains\Commissions\Support;

use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Visits\Models\Visit;

/**
 * Payload JSON de celebração pós-Contratar (mapa / first approach).
 */
final class CommissionAwardedPayload
{
    /**
     * @return array{amount: float, currency: string, sale_id: int|null, visit_id: int, play_reward: bool}|null
     */
    public static function fromVisit(Visit $visit): ?array
    {
        $visit->loadMissing(['sale.items']);

        $sale = $visit->sale;
        if ($sale === null) {
            return null;
        }

        $items = $sale->items;
        if ($items !== null && $items->isNotEmpty()) {
            $amount = (float) $items->sum(fn ($item) => (float) $item->commission_amount);
        } else {
            $amount = (float) SalesCommission::query()
                ->where('visit_id', $visit->id)
                ->sum('commission_amount');
        }

        $amount = round($amount, 2, PHP_ROUND_HALF_UP);

        return [
            'amount' => $amount,
            'currency' => 'BRL',
            'sale_id' => $sale->id,
            'visit_id' => $visit->id,
            'play_reward' => $amount > 0,
        ];
    }
}
