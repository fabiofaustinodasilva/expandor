<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Marketplace\Growth\DTOs\GrowthDashboardMetrics;
use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GrowthAnalyticsService
{
    public const CACHE_KEY = 'marketplace.growth.dashboard.v1';

    public function dashboard(): GrowthDashboardMetrics
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            $pageViews = MarketplaceAnalyticsService::PAGE_VIEW;
            $signups = MarketplaceAnalyticsService::SIGNUP_COMPLETED;
            $signupStarted = MarketplaceAnalyticsService::SIGNUP_STARTED;

            $visitsToday = $this->countEvents($pageViews, now()->startOfDay());
            $visitsWeek = $this->countEvents($pageViews, now()->startOfWeek());
            $visitsMonth = $this->countEvents($pageViews, now()->startOfMonth());
            $totalVisits = $this->countEvents($pageViews);
            $uniqueVisitors = (int) MarketplaceEvent::query()
                ->where('event', $pageViews)
                ->whereNotNull('session_id')
                ->selectRaw('COUNT(DISTINCT session_id) as aggregate')
                ->value('aggregate');

            $totalLeads = MarketplaceLead::query()->count();
            $trialsStarted = MarketplaceLead::query()
                ->where('status', MarketplaceLeadStatus::TrialStarted)
                ->count()
                + (int) MarketplaceEvent::query()->where('event', $signups)->count();
            $customers = MarketplaceLead::query()
                ->where('status', MarketplaceLeadStatus::Converted)
                ->count();

            $conversionRate = $uniqueVisitors > 0
                ? round(($totalLeads / $uniqueVisitors) * 100, 2)
                : 0.0;
            $signupConversion = $uniqueVisitors > 0
                ? round(($trialsStarted / $uniqueVisitors) * 100, 2)
                : 0.0;

            $funnel = [
                ['label' => 'Visitantes', 'value' => max($uniqueVisitors, $totalVisits)],
                ['label' => 'Leads', 'value' => $totalLeads],
                ['label' => 'Testes iniciados', 'value' => $trialsStarted],
                ['label' => 'Clientes', 'value' => $customers],
            ];

            return new GrowthDashboardMetrics(
                visitsToday: $visitsToday,
                visitsWeek: $visitsWeek,
                visitsMonth: $visitsMonth,
                totalVisits: $totalVisits,
                uniqueVisitors: $uniqueVisitors,
                totalLeads: $totalLeads,
                trialsStarted: $trialsStarted,
                customersConverted: $customers,
                conversionRate: $conversionRate,
                signupConversion: $signupConversion,
                funnel: $funnel,
                topSources: $this->topColumn('utm_source'),
                topCampaigns: $this->topColumn('utm_campaign'),
                topPages: $this->topPages(),
            );
        });
    }

    protected function countEvents(string $event, $since = null): int
    {
        $query = MarketplaceEvent::query()->where('event', $event);
        if ($since !== null) {
            $query->where('created_at', '>=', $since);
        }

        return (int) $query->count();
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    protected function topColumn(string $column, int $limit = 5): array
    {
        return MarketplaceEvent::query()
            ->select($column, DB::raw('COUNT(*) as total'))
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->{$column},
                'value' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    protected function topPages(int $limit = 5): array
    {
        return MarketplaceEvent::query()
            ->select('url', DB::raw('COUNT(*) as total'))
            ->where('event', MarketplaceAnalyticsService::PAGE_VIEW)
            ->whereNotNull('url')
            ->groupBy('url')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->url,
                'value' => (int) $row->total,
            ])
            ->all();
    }
}
