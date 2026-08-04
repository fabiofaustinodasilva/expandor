<?php

namespace App\Domains\Marketplace\Revenue\Events;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Revenue\Models\MarketplaceLeadScore;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketplaceLeadHotDetected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MarketplaceLead $lead,
        public MarketplaceLeadScore $score,
    ) {}
}
