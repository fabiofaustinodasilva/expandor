<?php

namespace App\Domains\Marketplace\Growth\DTOs;

readonly class RoiCalculation
{
    public function __construct(
        public int $sellers,
        public float $monthlySales,
        public float $averageTicket,
        public float $estimatedLossRate,
        public float $monthlyLoss,
        public float $potentialRecovery,
    ) {}

    /**
     * @return array<string, float|int>
     */
    public function toArray(): array
    {
        return [
            'sellers' => $this->sellers,
            'monthly_sales' => $this->monthlySales,
            'average_ticket' => $this->averageTicket,
            'estimated_loss_rate' => $this->estimatedLossRate,
            'monthly_loss' => $this->monthlyLoss,
            'potential_recovery' => $this->potentialRecovery,
        ];
    }
}
