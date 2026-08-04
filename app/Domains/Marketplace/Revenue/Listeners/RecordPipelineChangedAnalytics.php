<?php

namespace App\Domains\Marketplace\Revenue\Listeners;

use App\Domains\Marketplace\Growth\Services\ConversionTrackingService;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Events\MarketplacePipelineChanged;

class RecordPipelineChangedAnalytics
{
    public function __construct(
        protected ConversionTrackingService $tracking,
    ) {}

    public function handle(MarketplacePipelineChanged $event): void
    {
        $this->tracking->record('marketplace.pipeline_changed', [
            'lead_id' => $event->pipeline->lead_id,
            'metadata' => [
                'from' => $event->from->value,
                'to' => $event->to->value,
            ],
        ]);

        if ($event->to === PipelineStage::DemoScheduled) {
            $this->tracking->record('marketplace.demo_scheduled', [
                'lead_id' => $event->pipeline->lead_id,
            ]);
        }
    }
}
