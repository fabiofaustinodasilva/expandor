<?php

namespace App\Domains\Marketplace\Actions;

use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use Illuminate\Http\Request;

class RecordMarketplaceEventAction
{
    public function __construct(
        protected MarketplaceAnalyticsService $analytics,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(string $event, ?Request $request = null, array $metadata = []): void
    {
        $this->analytics->record($event, $request, $metadata);
    }
}
