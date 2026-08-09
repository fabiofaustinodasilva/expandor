<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Company\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Throttled, sanitized runtime fallback logging (no API keys).
 */
class MapProviderRuntimeReporter
{
    public function report(Company $company, string $code, string $message): void
    {
        $code = preg_replace('/[^a-z0-9_\-]/i', '', strtolower($code)) ?: 'unknown';
        $message = $this->sanitize($message);
        $cacheKey = 'integration.map.runtime.'.$company->id.'.'.$code;

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addHour());

        Log::warning('map.provider.runtime_fallback', [
            'company_id' => $company->id,
            'provider' => 'google_maps',
            'fallback' => 'leaflet_osm',
            'code' => $code,
            'message' => $message,
        ]);
    }

    private function sanitize(string $message): string
    {
        $clean = preg_replace('/AIza[0-9A-Za-z_\-]{10,}/', '[redacted]', $message) ?? $message;

        return mb_substr(trim($clean), 0, 220);
    }
}
