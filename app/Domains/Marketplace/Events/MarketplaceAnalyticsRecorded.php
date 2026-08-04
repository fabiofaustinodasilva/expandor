<?php

namespace App\Domains\Marketplace\Events;

use App\Domains\Marketplace\Models\MarketplaceEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketplaceAnalyticsRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MarketplaceEvent $event,
    ) {}
}
