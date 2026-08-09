<?php

namespace App\Domains\Commissions\Support;

use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Visits\Models\Visit;
use Illuminate\Support\Facades\Session;

/**
 * Payload JSON / flash de celebração pós-Contratar (mapa / first approach).
 *
 * Fonte: comissões persistidas — nunca recalcular no frontend.
 */
final class CommissionAwardedPayload
{
    public const SESSION_KEY = 'commission_awarded';

    /**
     * @return array{
     *     commission_id: int|null,
     *     amount: float,
     *     currency: string,
     *     sale_id: int|null,
     *     visit_id: int,
     *     awarded: bool,
     *     play_reward: bool
     * }|null
     */
    public static function fromVisit(Visit $visit): ?array
    {
        $visit->loadMissing(['sale']);

        $sale = $visit->sale;
        if ($sale === null) {
            return null;
        }

        $commissions = SalesCommission::query()
            ->where('visit_id', $visit->id)
            ->orderBy('id')
            ->get(['id', 'commission_amount']);

        if ($commissions->isEmpty()) {
            return null;
        }

        $amount = round((float) $commissions->sum(fn ($row) => (float) $row->commission_amount), 2, PHP_ROUND_HALF_UP);
        $awarded = $amount > 0;
        $primaryId = $awarded
            ? (int) ($commissions->first(fn ($row) => (float) $row->commission_amount > 0)?->id
                ?? $commissions->first()->id)
            : (int) $commissions->first()->id;

        $payload = [
            'commission_id' => $primaryId,
            'amount' => $amount,
            'currency' => 'BRL',
            'sale_id' => $sale->id,
            'visit_id' => $visit->id,
            'awarded' => $awarded,
            // Compat 8223: play_reward === awarded
            'play_reward' => $awarded,
        ];

        if ($awarded) {
            self::flashOnce($payload);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function flashOnce(array $payload): void
    {
        Session::flash(self::SESSION_KEY, $payload);
    }

    /**
     * Consome flash one-time (próximo GET do mapa).
     *
     * @return array<string, mixed>|null
     */
    public static function pullFlash(): ?array
    {
        $raw = Session::pull(self::SESSION_KEY);
        if (! is_array($raw)) {
            return null;
        }

        if (empty($raw['awarded']) && empty($raw['play_reward'])) {
            return null;
        }

        return $raw;
    }
}
