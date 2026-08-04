<?php

namespace App\Domains\Marketplace\Services;

use App\Domains\Marketplace\Growth\Services\ConversionTrackingService;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use Illuminate\Http\Request;

/**
 * Facade de analytics do Marketplace — delega ao Growth ConversionTrackingService.
 * Mantém constantes e API usadas pelo CMS (Sprint 7.6).
 */
class MarketplaceAnalyticsService
{
    public const PAGE_VIEW = 'marketplace.page_view';

    public const HERO_VIEW = 'marketplace.hero_view';

    public const FEATURE_VIEW = 'marketplace.feature_view';

    public const VIDEO_STARTED = 'marketplace.video_started';

    public const PLAN_VIEW = 'marketplace.plan_view';

    public const PLAN_CLICKED = 'marketplace.plan_clicked';

    public const WHATSAPP_CLICKED = 'marketplace.whatsapp_clicked';

    public const INSTAGRAM_CLICKED = 'marketplace.instagram_clicked';

    public const SIGNUP_STARTED = 'marketplace.signup_started';

    public const SIGNUP_COMPLETED = 'marketplace.signup_completed';

    public const LEAD_CREATED = 'marketplace.lead_created';

    public const ROI_CALCULATED = 'marketplace.roi_calculated';

    public function __construct(
        protected ConversionTrackingService $tracking,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(string $event, ?Request $request = null, array $metadata = []): MarketplaceEvent
    {
        $overrides = [];
        if ($metadata !== []) {
            $overrides['metadata'] = $metadata;
            if (isset($metadata['plan_id'])) {
                $overrides['metadata'] = $metadata;
            }
            if (isset($metadata['company_id'])) {
                $overrides['company_id'] = $metadata['company_id'];
            }
            if (isset($metadata['lead_id'])) {
                $overrides['lead_id'] = $metadata['lead_id'];
            }
        }

        $this->tracking->record($event, $overrides, $request);

        return MarketplaceEvent::query()
            ->where('event', $event)
            ->latest('id')
            ->first() ?? new MarketplaceEvent(['event' => $event, 'created_at' => now()]);
    }

    public function trackFromRequest(Request $request): MarketplaceEvent
    {
        $this->tracking->trackFromRequest($request);

        $event = (string) $request->input('event', self::PAGE_VIEW);

        return MarketplaceEvent::query()
            ->where('event', $event)
            ->latest('id')
            ->first() ?? new MarketplaceEvent(['event' => $event, 'created_at' => now()]);
    }
}
