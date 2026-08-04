<?php

namespace App\Domains\Marketplace\Revenue\Services;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Revenue\Enums\LeadTemperature;
use App\Domains\Marketplace\Revenue\Events\MarketplaceLeadHotDetected;
use App\Domains\Marketplace\Revenue\Events\MarketplaceLeadScored;
use App\Domains\Marketplace\Revenue\Models\MarketplaceLeadScore;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeadScoringService
{
    public const POINTS_PRICING = 10;

    public const POINTS_VIDEO = 15;

    public const POINTS_ROI = 20;

    public const POINTS_WHATSAPP = 25;

    public const POINTS_DEMO = 40;

    public function scoreForLead(MarketplaceLead $lead): MarketplaceLeadScore
    {
        return DB::transaction(function () use ($lead) {
            $signals = $this->detectSignals($lead);

            $row = MarketplaceLeadScore::query()->firstOrNew(['lead_id' => $lead->id]);
            $wasHot = $row->exists && $row->score >= 71;

            $row->visited_pricing = $signals['visited_pricing'];
            $row->watched_video = $signals['watched_video'];
            $row->clicked_whatsapp = $signals['clicked_whatsapp'];
            $row->used_roi_calculator = $signals['used_roi_calculator'];
            $row->requested_demo = $signals['requested_demo'];
            $row->started_trial = $signals['started_trial'];
            $row->score = $this->calculateScore($signals);
            $row->temperature = LeadTemperature::fromScore($row->score);

            $becameHot = ! $wasHot && $row->score >= 71;
            if ($becameHot && $row->hot_detected_at === null) {
                $row->hot_detected_at = now();
            }

            $row->save();

            event(new MarketplaceLeadScored($lead, $row));

            if ($becameHot) {
                event(new MarketplaceLeadHotDetected($lead, $row));
            }

            Cache::forget('marketplace.revenue.intelligence.v1');

            return $row->fresh() ?? $row;
        });
    }

    /**
     * @param  array<string, bool>  $signals
     */
    public function calculateScore(array $signals): int
    {
        $score = 0;
        if (! empty($signals['visited_pricing'])) {
            $score += self::POINTS_PRICING;
        }
        if (! empty($signals['watched_video'])) {
            $score += self::POINTS_VIDEO;
        }
        if (! empty($signals['used_roi_calculator'])) {
            $score += self::POINTS_ROI;
        }
        if (! empty($signals['clicked_whatsapp'])) {
            $score += self::POINTS_WHATSAPP;
        }
        if (! empty($signals['requested_demo'])) {
            $score += self::POINTS_DEMO;
        }

        return min(100, $score);
    }

    public function temperatureForScore(int $score): LeadTemperature
    {
        return LeadTemperature::fromScore($score);
    }

    /**
     * @return array{
     *   visited_pricing: bool,
     *   watched_video: bool,
     *   clicked_whatsapp: bool,
     *   used_roi_calculator: bool,
     *   requested_demo: bool,
     *   started_trial: bool
     * }
     */
    public function detectSignals(MarketplaceLead $lead): array
    {
        $eventNames = MarketplaceEvent::query()
            ->where(function ($q) use ($lead): void {
                $q->where('lead_id', $lead->id);
                if (filled($lead->session_id)) {
                    $q->orWhere('session_id', $lead->session_id);
                }
            })
            ->pluck('event')
            ->unique()
            ->all();

        $has = fn (string ...$names): bool => count(array_intersect($names, $eventNames)) > 0;

        return [
            'visited_pricing' => $has(
                MarketplaceAnalyticsService::PLAN_VIEW,
                MarketplaceAnalyticsService::PLAN_CLICKED,
            ),
            'watched_video' => $has(MarketplaceAnalyticsService::VIDEO_STARTED),
            'clicked_whatsapp' => $has(MarketplaceAnalyticsService::WHATSAPP_CLICKED),
            'used_roi_calculator' => $has(MarketplaceAnalyticsService::ROI_CALCULATED),
            'requested_demo' => true, // lead exists = demo/capture
            'started_trial' => $has(MarketplaceAnalyticsService::SIGNUP_COMPLETED)
                || $has('marketplace.trial.started'),
        ];
    }
}
