<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Growth\Services\GrowthAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MarketplaceAnalyticsDashboardController extends Controller
{
    public function __invoke(GrowthAnalyticsService $analytics): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.growth.analytics', [
            'metrics' => $analytics->dashboard(),
        ]);
    }
}
