<?php

namespace App\Domains\Marketplace\Revenue\Events;

use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketplaceDemoScheduled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MarketplaceSalesPipeline $pipeline,
    ) {}
}
