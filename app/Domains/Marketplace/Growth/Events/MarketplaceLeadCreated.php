<?php

namespace App\Domains\Marketplace\Growth\Events;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketplaceLeadCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MarketplaceLead $lead,
    ) {}
}
