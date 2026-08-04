<?php

namespace App\Domains\Marketplace\Revenue\Listeners;

use App\Domains\Marketplace\Growth\Events\MarketplaceLeadCreated;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Services\LeadScoringService;
use App\Domains\Marketplace\Revenue\Services\MarketplacePipelineService;

class BootstrapLeadRevenueOnCreated
{
    public function __construct(
        protected LeadScoringService $scoring,
        protected MarketplacePipelineService $pipeline,
    ) {}

    public function handle(MarketplaceLeadCreated $event): void
    {
        $this->pipeline->ensureForLead($event->lead, PipelineStage::New);
        $this->scoring->scoreForLead($event->lead);
    }
}
