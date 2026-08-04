<?php

namespace App\Domains\Marketplace\Revenue\Listeners;

use App\Domains\Marketplace\Growth\Events\MarketplaceGrowthEventRecorded;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Revenue\Services\LeadScoringService;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;

class RescoreLeadOnGrowthEvent
{
    public function __construct(
        protected LeadScoringService $scoring,
    ) {}

    public function handle(MarketplaceGrowthEventRecorded $event): void
    {
        $payload = $event->event;
        $lead = null;

        if ($payload->lead_id) {
            $lead = MarketplaceLead::query()->find($payload->lead_id);
        } elseif (filled($payload->session_id)) {
            $lead = MarketplaceLead::query()
                ->where('session_id', $payload->session_id)
                ->latest('id')
                ->first();
        }

        if ($lead === null) {
            return;
        }

        $relevant = [
            MarketplaceAnalyticsService::PLAN_VIEW,
            MarketplaceAnalyticsService::PLAN_CLICKED,
            MarketplaceAnalyticsService::VIDEO_STARTED,
            MarketplaceAnalyticsService::WHATSAPP_CLICKED,
            MarketplaceAnalyticsService::ROI_CALCULATED,
            MarketplaceAnalyticsService::SIGNUP_COMPLETED,
            MarketplaceAnalyticsService::LEAD_CREATED,
        ];

        if (! in_array($payload->event, $relevant, true)) {
            return;
        }

        $this->scoring->scoreForLead($lead);
    }
}
