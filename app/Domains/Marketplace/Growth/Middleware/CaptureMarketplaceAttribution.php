<?php

namespace App\Domains\Marketplace\Growth\Middleware;

use App\Domains\Marketplace\Growth\Services\MarketplaceAttributionService;
use Closure;
use Illuminate\Http\Request;

class CaptureMarketplaceAttribution
{
    public function __construct(
        protected MarketplaceAttributionService $attribution,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->hasSession()) {
            $this->attribution->ensureSessionId($request);
            $this->attribution->captureUtm($request);
        }

        return $next($request);
    }
}
