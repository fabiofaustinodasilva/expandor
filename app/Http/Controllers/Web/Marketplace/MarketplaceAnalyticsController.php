<?php

namespace App\Http\Controllers\Web\Marketplace;

use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceAnalyticsController extends Controller
{
    public function store(Request $request, MarketplaceAnalyticsService $analytics): JsonResponse
    {
        $analytics->trackFromRequest($request);

        return response()->json(['ok' => true]);
    }
}
