<?php

namespace App\Domains\Marketplace\Growth\Listeners;

use App\Domains\Marketplace\Growth\Events\MarketplaceLeadCreated;
use App\Domains\Marketplace\Growth\Services\ConversionTrackingService;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;

class RecordLeadCreatedAnalytics
{
    public function __construct(
        protected ConversionTrackingService $tracking,
    ) {}

    public function handle(MarketplaceLeadCreated $event): void
    {
        $this->tracking->record(MarketplaceAnalyticsService::LEAD_CREATED, [
            'lead_id' => $event->lead->id,
            'metadata' => [
                'email' => $event->lead->email,
                'segment' => $event->lead->segment,
                'source' => $event->lead->source,
            ],
        ]);
    }
}
