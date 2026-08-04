<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Marketplace\Growth\DTOs\RoiCalculation;

class MarketplaceRoiCalculator
{
    public function calculate(
        int $sellers,
        float $monthlySales,
        float $averageTicket,
        float $estimatedLossPercent,
    ): RoiCalculation {
        $sellers = max(0, $sellers);
        $monthlySales = max(0, $monthlySales);
        $averageTicket = max(0, $averageTicket);
        $rate = max(0, min(100, $estimatedLossPercent)) / 100;

        $revenue = $monthlySales > 0
            ? $monthlySales * $averageTicket
            : $sellers * 20 * $averageTicket;

        $monthlyLoss = round($revenue * $rate, 2);
        $potentialRecovery = round($monthlyLoss * 0.65, 2);

        return new RoiCalculation(
            sellers: $sellers,
            monthlySales: $monthlySales,
            averageTicket: $averageTicket,
            estimatedLossRate: $rate,
            monthlyLoss: $monthlyLoss,
            potentialRecovery: $potentialRecovery,
        );
    }
}
