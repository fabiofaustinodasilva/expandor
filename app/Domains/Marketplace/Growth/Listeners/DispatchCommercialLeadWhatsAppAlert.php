<?php

namespace App\Domains\Marketplace\Growth\Listeners;

use App\Domains\Marketplace\Growth\Events\MarketplaceLeadCreated;
use App\Domains\Marketplace\Growth\Jobs\SendNewDemoLeadWhatsAppNotification;
use App\Domains\Marketplace\Growth\Services\LeadActivityService;

class DispatchCommercialLeadWhatsAppAlert
{
    public function __construct(
        protected LeadActivityService $activities,
    ) {}

    public function handle(MarketplaceLeadCreated $event): void
    {
        $this->activities->record(
            $event->lead,
            'lead_received',
            'Lead recebido',
        );

        SendNewDemoLeadWhatsAppNotification::dispatch($event->lead->id)->afterCommit();
    }
}
