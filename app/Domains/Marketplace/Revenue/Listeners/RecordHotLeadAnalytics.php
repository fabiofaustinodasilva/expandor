<?php

namespace App\Domains\Marketplace\Revenue\Listeners;

use App\Domains\Marketplace\Growth\Services\ConversionTrackingService;
use App\Domains\Marketplace\Revenue\Events\MarketplaceLeadHotDetected;

class RecordHotLeadAnalytics
{
    public function __construct(
        protected ConversionTrackingService $tracking,
    ) {}

    public function handle(MarketplaceLeadHotDetected $event): void
    {
        $lead = $event->lead;
        $this->tracking->record('marketplace.lead_hot_detected', [
            'lead_id' => $lead->id,
            'metadata' => [
                'score' => $event->score->score,
                'source' => $lead->source,
                'utm_source' => $lead->utm_source,
                'utm_campaign' => $lead->utm_campaign,
                'company_name' => $lead->company_name,
            ],
        ]);
    }
}
