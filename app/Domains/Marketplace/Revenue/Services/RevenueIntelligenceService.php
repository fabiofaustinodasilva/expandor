<?php

namespace App\Domains\Marketplace\Revenue\Services;

use App\Domains\Marketplace\Growth\Models\MarketplaceCampaign;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Revenue\DTOs\RevenueIntelligenceMetrics;
use App\Domains\Marketplace\Revenue\Enums\LeadTemperature;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Models\MarketplaceLeadScore;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use Illuminate\Support\Facades\Cache;

class RevenueIntelligenceService
{
    public const CACHE_KEY = 'marketplace.revenue.intelligence.v1';

    public function dashboard(): RevenueIntelligenceMetrics
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            $visitors = (int) MarketplaceEvent::query()
                ->where('event', MarketplaceAnalyticsService::PAGE_VIEW)
                ->whereNotNull('session_id')
                ->selectRaw('COUNT(DISTINCT session_id) as aggregate')
                ->value('aggregate');

            $leads = MarketplaceLead::query()->count();
            $demos = MarketplaceLead::query()->count(); // demo form creates leads
            $hot = MarketplaceLeadScore::query()
                ->where(function ($q): void {
                    $q->where('temperature', LeadTemperature::Hot->value)
                        ->orWhere('score', '>=', 71);
                })
                ->count();

            $trials = MarketplaceSalesPipeline::query()
                ->where('stage', PipelineStage::TrialStarted->value)
                ->count()
                + MarketplaceLead::query()->where('status', MarketplaceLeadStatus::TrialStarted)->count();

            // unique-ish: prefer pipeline customer stage
            $customers = MarketplaceSalesPipeline::query()
                ->where('stage', PipelineStage::Customer->value)
                ->count()
                + MarketplaceLead::query()->where('status', MarketplaceLeadStatus::Converted)->count();

            $conversion = $visitors > 0 ? round(($leads / $visitors) * 100, 2) : 0.0;

            $hotLeads = MarketplaceLeadScore::query()
                ->with('lead')
                ->where(function ($q): void {
                    $q->where('temperature', LeadTemperature::Hot->value)
                        ->orWhere('score', '>=', 71);
                })
                ->orderByDesc('score')
                ->limit(10)
                ->get()
                ->map(fn (MarketplaceLeadScore $s) => [
                    'id' => $s->lead_id,
                    'name' => $s->lead?->name ?? 'Lead #'.$s->lead_id,
                    'score' => $s->score,
                    'source' => $s->lead?->source,
                    'utm' => $s->lead?->utm_campaign ?? $s->lead?->utm_source,
                ])
                ->all();

            return new RevenueIntelligenceMetrics(
                visitors: $visitors,
                leads: $leads,
                conversionRate: $conversion,
                hotLeadsCount: $hot,
                demosRequested: $demos,
                trialsStarted: $trials,
                customersConverted: $customers,
                funnel: [
                    ['label' => 'Visitantes', 'value' => $visitors],
                    ['label' => 'Leads', 'value' => $leads],
                    ['label' => 'Quentes', 'value' => $hot],
                    ['label' => 'Trials', 'value' => $trials],
                    ['label' => 'Clientes', 'value' => $customers],
                ],
                campaigns: $this->campaignComparison(),
                hotLeads: $hotLeads,
            );
        });
    }

    /**
     * @return array<int, array{campaign: string, investment: float, leads: int, customers: int, cac: float|null, roi: float|null}>
     */
    public function campaignComparison(): array
    {
        return MarketplaceCampaign::query()
            ->orderByDesc('id')
            ->get()
            ->map(function (MarketplaceCampaign $campaign) {
                $utm = $campaign->campaign ?: $campaign->name;
                $leads = MarketplaceLead::query()
                    ->where(function ($q) use ($campaign, $utm): void {
                        if (filled($campaign->campaign)) {
                            $q->orWhere('utm_campaign', $campaign->campaign);
                        }
                        if (filled($campaign->source)) {
                            $q->orWhere('utm_source', $campaign->source);
                        }
                        $q->orWhere('utm_campaign', $utm);
                    })
                    ->count();

                $customers = MarketplaceLead::query()
                    ->where('status', MarketplaceLeadStatus::Converted)
                    ->where(function ($q) use ($campaign, $utm): void {
                        if (filled($campaign->campaign)) {
                            $q->orWhere('utm_campaign', $campaign->campaign);
                        }
                        $q->orWhere('utm_campaign', $utm);
                    })
                    ->count();

                $investment = (float) ($campaign->investment ?? 0);
                $cac = $customers > 0 ? round($investment / $customers, 2) : null;
                // Assume R$ 297/mês avg plan value * 12 for rough LTV proxy when ROI unknown
                $assumedRevenue = $customers * 297 * 12;
                $roi = $investment > 0
                    ? round((($assumedRevenue - $investment) / $investment) * 100, 2)
                    : null;

                return [
                    'campaign' => $campaign->name,
                    'investment' => $investment,
                    'leads' => $leads,
                    'customers' => $customers,
                    'cac' => $cac,
                    'roi' => $roi,
                ];
            })
            ->all();
    }
}
