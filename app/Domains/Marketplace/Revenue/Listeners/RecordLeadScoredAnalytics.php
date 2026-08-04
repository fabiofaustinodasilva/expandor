<?php

namespace App\Domains\Marketplace\Revenue\Listeners;

use App\Domains\Marketplace\Growth\Services\ConversionTrackingService;
use App\Domains\Marketplace\Revenue\Events\MarketplaceLeadScored;

class RecordLeadScoredAnalytics
{
    public function __construct(
        protected ConversionTrackingService $tracking,
    ) {}

    public function handle(MarketplaceLeadScored $event): void
    {
        $this->tracking->record('marketplace.lead_scored', [
            'lead_id' => $event->lead->id,
            'metadata' => [
                'score' => $event->score->score,
                'temperature' => $event->score->temperature?->value,
            ],
        ]);
    }
}
