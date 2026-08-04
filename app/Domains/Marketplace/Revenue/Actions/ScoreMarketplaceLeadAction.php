<?php

namespace App\Domains\Marketplace\Revenue\Actions;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Revenue\Models\MarketplaceLeadScore;
use App\Domains\Marketplace\Revenue\Services\LeadScoringService;

class ScoreMarketplaceLeadAction
{
    public function __construct(
        protected LeadScoringService $scoring,
    ) {}

    public function execute(MarketplaceLead $lead): MarketplaceLeadScore
    {
        return $this->scoring->scoreForLead($lead);
    }
}
