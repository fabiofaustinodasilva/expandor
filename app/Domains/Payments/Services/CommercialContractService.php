<?php

namespace App\Domains\Payments\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CommercialContractService
{
    public const BILLING_DAYS = [5, 10, 15, 20, 25, 28];

    public const FIDELITY_NONE = 'none';

    public const FIDELITY_3 = '3';

    public const FIDELITY_6 = '6';

    public const FIDELITY_12 = '12';

    public const FIDELITY_CUSTOM = 'custom';

    /**
     * Primeiro vencimento: próximo dia de cobrança >= data de início do contrato.
     * Se o dia já passou no mês do início, usa o mês seguinte.
     */
    public function firstDueDate(CarbonInterface $contractStartedAt, int $billingDay): CarbonInterface
    {
        $billingDay = $this->normalizeBillingDay($billingDay);
        $start = Carbon::parse($contractStartedAt)->startOfDay();
        $candidate = $start->copy()->day(min($billingDay, $start->daysInMonth))->startOfDay();

        if ($candidate->lt($start)) {
            $next = $start->copy()->addMonthNoOverflow();
            $candidate = $next->copy()->day(min($billingDay, $next->daysInMonth))->startOfDay();
        }

        return $candidate;
    }

    public function normalizeBillingDay(int $billingDay): int
    {
        if (! in_array($billingDay, self::BILLING_DAYS, true)) {
            throw new InvalidArgumentException('Dia de vencimento inválido.');
        }

        return $billingDay;
    }

    /**
     * @return array{months: ?int, ends_at: ?CarbonInterface}
     */
    public function resolveFidelity(
        string $mode,
        CarbonInterface $contractStartedAt,
        ?int $customMonths = null,
    ): array {
        $start = Carbon::parse($contractStartedAt)->startOfDay();

        $months = match ($mode) {
            self::FIDELITY_NONE => null,
            self::FIDELITY_3 => 3,
            self::FIDELITY_6 => 6,
            self::FIDELITY_12 => 12,
            self::FIDELITY_CUSTOM => $customMonths,
            default => throw new InvalidArgumentException('Opção de fidelidade inválida.'),
        };

        if ($mode === self::FIDELITY_CUSTOM) {
            if ($customMonths === null || $customMonths < 1 || $customMonths > 60) {
                throw new InvalidArgumentException('Fidelidade personalizada deve ter entre 1 e 60 meses.');
            }
        }

        if ($months === null) {
            return ['months' => null, 'ends_at' => null];
        }

        return [
            'months' => $months,
            'ends_at' => $start->copy()->addMonthsNoOverflow($months),
        ];
    }

    public function resolveContractedAmount(float $planPrice, bool $special, ?float $negotiatedAmount): float
    {
        if (! $special) {
            return round($planPrice, 2);
        }

        if ($negotiatedAmount === null || $negotiatedAmount < 0) {
            throw new InvalidArgumentException('Mensalidade negociada inválida.');
        }

        return round($negotiatedAmount, 2);
    }
}
