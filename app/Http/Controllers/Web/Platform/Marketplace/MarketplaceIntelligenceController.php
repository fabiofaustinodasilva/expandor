<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Revenue\Services\RevenueIntelligenceService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MarketplaceIntelligenceController extends Controller
{
    public function __invoke(RevenueIntelligenceService $intelligence): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.revenue.intelligence', [
            'metrics' => $intelligence->dashboard(),
        ]);
    }
}
