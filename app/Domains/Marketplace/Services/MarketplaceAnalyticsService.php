<?php

namespace App\Domains\Marketplace\Services;

use App\Domains\Marketplace\Models\MarketplaceEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketplaceAnalyticsService
{
    public const PAGE_VIEW = 'marketplace.page_view';

    public const WHATSAPP_CLICKED = 'marketplace.whatsapp_clicked';

    public const INSTAGRAM_CLICKED = 'marketplace.instagram_clicked';

    public const VIDEO_STARTED = 'marketplace.video_started';

    public const PLAN_CLICKED = 'marketplace.plan_clicked';

    public const SIGNUP_STARTED = 'marketplace.signup_started';

    public const SIGNUP_COMPLETED = 'marketplace.signup_completed';

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(string $event, ?Request $request = null, array $metadata = []): MarketplaceEvent
    {
        $request ??= request();

        try {
            return MarketplaceEvent::query()->create([
                'event' => $event,
                'ip_hash' => $this->hashIp($request->ip()),
                'user_agent' => substr((string) $request->userAgent(), 0, 2000),
                'origin' => $request->headers->get('referer')
                    ?? $request->query('utm_source')
                    ?? $request->input('origin'),
                'metadata' => $metadata !== [] ? $metadata : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('marketplace.analytics_failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return new MarketplaceEvent([
                'event' => $event,
                'created_at' => now(),
            ]);
        }
    }

    public function trackFromRequest(Request $request): MarketplaceEvent
    {
        $event = (string) $request->input('event', self::PAGE_VIEW);
        $allowed = [
            self::PAGE_VIEW,
            self::WHATSAPP_CLICKED,
            self::INSTAGRAM_CLICKED,
            self::VIDEO_STARTED,
            self::PLAN_CLICKED,
            self::SIGNUP_STARTED,
            self::SIGNUP_COMPLETED,
        ];

        if (! in_array($event, $allowed, true)) {
            $event = self::PAGE_VIEW;
        }

        $metadata = $request->input('metadata');
        if (! is_array($metadata)) {
            $metadata = [];
        }

        return $this->record($event, $request, $metadata);
    }

    protected function hashIp(?string $ip): ?string
    {
        if (! filled($ip)) {
            return null;
        }

        $salt = (string) config('app.key');

        return hash('sha256', $salt.'|'.$ip);
    }
}
