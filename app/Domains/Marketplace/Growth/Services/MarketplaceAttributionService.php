<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Marketplace\Growth\DTOs\UtmAttribution;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketplaceAttributionService
{
    public const SESSION_ID_KEY = 'marketplace.session_id';

    public const UTM_KEY = 'marketplace.utm';

    public function ensureSessionId(Request $request): string
    {
        $existing = $request->session()->get(self::SESSION_ID_KEY);
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $id = (string) Str::uuid();
        $request->session()->put(self::SESSION_ID_KEY, $id);

        return $id;
    }

    public function captureUtm(Request $request): UtmAttribution
    {
        $fromQuery = UtmAttribution::fromArray([
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_term' => $request->query('utm_term'),
            'utm_content' => $request->query('utm_content'),
        ]);

        if ($fromQuery->hasAny()) {
            $request->session()->put(self::UTM_KEY, $fromQuery->toArray());

            return $fromQuery;
        }

        $stored = $request->session()->get(self::UTM_KEY, []);

        return UtmAttribution::fromArray(is_array($stored) ? $stored : []);
    }

    public function currentUtm(Request $request): UtmAttribution
    {
        $stored = $request->session()->get(self::UTM_KEY, []);

        return UtmAttribution::fromArray(is_array($stored) ? $stored : []);
    }

    public function detectDevice(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);
        if ($ua === '') {
            return 'unknown';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        return 'desktop';
    }
}
