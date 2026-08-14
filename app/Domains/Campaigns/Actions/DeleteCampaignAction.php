<?php

namespace App\Domains\Campaigns\Actions;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Campaigns\Services\CampaignService;

class DeleteCampaignAction
{
    public function __construct(
        protected CampaignService $campaigns
    ) {}

    public function execute(Campaign $campaign): void
    {
        $this->campaigns->delete($campaign);
    }
}
