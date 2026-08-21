<?php

namespace App\Domains\Marketplace\Growth\Listeners;

use App\Domains\Marketplace\Growth\Events\MarketplaceLeadCreated;
use App\Domains\Marketplace\Growth\Jobs\SendNewDemoLeadWhatsAppNotification;
use App\Domains\Marketplace\Growth\Services\LeadActivityService;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;

class DispatchCommercialLeadWhatsAppAlert
{
    public function __construct(
        protected LeadActivityService $activities,
        protected MarketplaceSettingsService $settings,
    ) {}

    public function handle(MarketplaceLeadCreated $event): void
    {
        $this->activities->record(
            $event->lead,
            'lead_received',
            'Lead recebido',
        );

        // Optional / experimental: only enqueue WppConnect alert when explicitly enabled.
        // The primary commercial flow is confirmation page + visitor wa.me CTA.
        $settings = $this->settings->current();
        if (! $settings->commercial_alert_enabled) {
            return;
        }

        SendNewDemoLeadWhatsAppNotification::dispatch($event->lead->id)->afterCommit();
    }
}
