<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Marketplace\Growth\DTOs\UtmAttribution;
use App\Domains\Marketplace\Growth\Jobs\PersistMarketplaceGrowthEventJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConversionTrackingService
{
    public function __construct(
        protected MarketplaceAttributionService $attribution,
    ) {}

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function record(string $event, array $overrides = [], ?Request $request = null): void
    {
        $request ??= request();

        try {
            if ($request->hasSession()) {
                $this->attribution->ensureSessionId($request);
                $this->attribution->captureUtm($request);
            }

            $utm = $request->hasSession()
                ? $this->attribution->currentUtm($request)
                : UtmAttribution::fromArray($overrides);

            $sessionId = $overrides['session_id']
                ?? ($request->hasSession() ? $request->session()->get(MarketplaceAttributionService::SESSION_ID_KEY) : null);

            $payload = [
                'event' => $event,
                'session_id' => $sessionId,
                'company_id' => $overrides['company_id'] ?? null,
                'lead_id' => $overrides['lead_id'] ?? null,
                'url' => $overrides['url'] ?? substr((string) $request->fullUrl(), 0, 500),
                'referrer' => $overrides['referrer']
                    ?? (substr((string) ($request->headers->get('referer') ?? ''), 0, 500) ?: null),
                'utm_source' => $overrides['utm_source'] ?? $utm->source,
                'utm_medium' => $overrides['utm_medium'] ?? $utm->medium,
                'utm_campaign' => $overrides['utm_campaign'] ?? $utm->campaign,
                'utm_term' => $overrides['utm_term'] ?? $utm->term,
                'utm_content' => $overrides['utm_content'] ?? $utm->content,
                'device' => $overrides['device']
                    ?? $this->attribution->detectDevice($request->userAgent()),
                'ip_hash' => $overrides['ip_hash'] ?? $this->hashIp($request->ip()),
                'user_agent' => substr((string) $request->userAgent(), 0, 2000) ?: null,
                'origin' => $overrides['origin']
                    ?? $request->headers->get('referer')
                    ?? $utm->source,
                'metadata' => $overrides['metadata'] ?? null,
                'created_at' => now()->toDateTimeString(),
            ];

            if (app()->environment('testing') || config('queue.default') === 'sync') {
                (new PersistMarketplaceGrowthEventJob($payload))->handle();
            } else {
                PersistMarketplaceGrowthEventJob::dispatch($payload);
            }
        } catch (\Throwable $e) {
            Log::warning('marketplace.growth.track_failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function trackFromRequest(Request $request): void
    {
        $event = (string) $request->input('event', 'marketplace.page_view');
        $allowed = [
            'marketplace.page_view',
            'marketplace.hero_view',
            'marketplace.feature_view',
            'marketplace.video_started',
            'marketplace.plan_view',
            'marketplace.plan_clicked',
            'marketplace.whatsapp_clicked',
            'marketplace.instagram_clicked',
            'marketplace.signup_started',
            'marketplace.signup_completed',
            'marketplace.lead_created',
            'marketplace.roi_calculated',
            'marketplace.lead_scored',
            'marketplace.lead_hot_detected',
            'marketplace.pipeline_changed',
            'marketplace.demo_scheduled',
        ];

        if (! in_array($event, $allowed, true)) {
            $event = 'marketplace.page_view';
        }

        $metadata = $request->input('metadata');
        if (! is_array($metadata)) {
            $metadata = [];
        }

        $this->record($event, [
            'lead_id' => $request->input('lead_id'),
            'company_id' => $request->input('company_id'),
            'url' => $request->input('url') ?: $request->headers->get('referer'),
            'metadata' => $metadata,
        ], $request);
    }

    protected function hashIp(?string $ip): ?string
    {
        if (! filled($ip)) {
            return null;
        }

        return hash('sha256', (string) config('app.key').'|'.$ip);
    }
}
