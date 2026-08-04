<?php

namespace App\Domains\Marketplace\Growth\Actions;

use App\Domains\Marketplace\Growth\DTOs\RoiCalculation;
use App\Domains\Marketplace\Growth\Services\ConversionTrackingService;
use App\Domains\Marketplace\Growth\Services\MarketplaceRoiCalculator;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;

class CalculateMarketplaceRoiAction
{
    public function __construct(
        protected MarketplaceRoiCalculator $calculator,
        protected ConversionTrackingService $tracking,
    ) {}

    public function execute(
        int $sellers,
        float $monthlySales,
        float $averageTicket,
        float $estimatedLossPercent,
    ): RoiCalculation {
        $result = $this->calculator->calculate($sellers, $monthlySales, $averageTicket, $estimatedLossPercent);

        $this->tracking->record(MarketplaceAnalyticsService::ROI_CALCULATED, [
            'metadata' => $result->toArray(),
        ]);

        return $result;
    }
}
