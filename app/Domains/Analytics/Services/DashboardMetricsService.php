<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\DTOs\AnalyticsFiltersDTO;
use App\Domains\Analytics\DTOs\DashboardMetricsDTO;
use App\Domains\Analytics\Repositories\AnalyticsRepository;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Visits\Enums\VisitStatus;
use App\Support\CommercialTerminology;

class DashboardMetricsService
{
    public function __construct(
        protected AnalyticsRepository $repository
    ) {}

    public function metrics(AnalyticsFiltersDTO $filters, ?User $viewer = null): DashboardMetricsDTO
    {
        $teamView = $viewer === null || $viewer->role?->slug !== Role::SELLER;

        $visitsTotal = $this->repository->countVisits($filters);
        $installations = $this->repository->countVisitsByStatus(
            $filters,
            VisitStatus::INSTALLATION_REQUESTED
        );
        $interested = $this->repository->countVisitsByStatus(
            $filters,
            VisitStatus::INTERESTED
        );
        $propertiesTotal = $this->repository->countProperties($filters);

        $sellerProductivity = $this->repository->sellerProductivity($filters);
        $sectorPerformance = $teamView
            ? $this->repository->sectorPerformance($filters)
            : [];
        $sellerRanking = $teamView
            ? $this->repository->sellerRanking($filters)
            : array_slice($sellerProductivity, 0, 1);

        return new DashboardMetricsDTO(
            properties_total: $propertiesTotal,
            visits_total: $visitsTotal,
            interested_total: $interested,
            installations_total: $installations,
            conversion_rate: $this->repository->rate($installations, $visitsTotal),
            pending_follow_ups: $this->repository->countPendingFollowUps($filters),
            visits_by_status: $this->repository->visitsGroupedByStatus($filters),
            visits_by_period: $this->repository->visitsByPeriod($filters),
            seller_productivity: $sellerProductivity,
            sector_performance: $sectorPerformance,
            seller_ranking: $sellerRanking,
            funnel: $this->buildFunnel($propertiesTotal, $visitsTotal, $interested, $installations),
            productivity: $this->buildProductivity($sellerProductivity, $visitsTotal, $installations),
            alerts: $teamView
                ? $this->buildAlerts($filters, $sectorPerformance)
                : $this->buildSellerAlerts($filters),
            team_view: $teamView,
        );
    }

    /**
     * @return array{points: int, visits: int, interested: int, contracts: int, pct_visits: float, pct_interested: float, pct_contracts: float}
     */
    protected function buildFunnel(int $points, int $visits, int $interested, int $contracts): array
    {
        return [
            'points' => $points,
            'visits' => $visits,
            'interested' => $interested,
            'contracts' => $contracts,
            'pct_visits' => $this->repository->rate($visits, $points),
            'pct_interested' => $this->repository->rate($interested, $visits),
            'pct_contracts' => $this->repository->rate($contracts, $interested),
        ];
    }

    /**
     * @param  list<array{user_id: int, name: string, visits: int, interested: int, installations: int, conversion_rate: float}>  $sellers
     * @return array{active_sellers: int, avg_visits: float, avg_contracts: float, best_seller: ?array{user_id: int, name: string, installations: int, conversion_rate: float}}
     */
    protected function buildProductivity(array $sellers, int $visitsTotal, int $installationsTotal): array
    {
        $active = count($sellers);
        $best = $sellers[0] ?? null;

        return [
            'active_sellers' => $active,
            'avg_visits' => $active > 0 ? round($visitsTotal / $active, 1) : 0.0,
            'avg_contracts' => $active > 0 ? round($installationsTotal / $active, 1) : 0.0,
            'best_seller' => $best ? [
                'user_id' => $best['user_id'],
                'name' => $best['name'],
                'installations' => $best['installations'],
                'conversion_rate' => $best['conversion_rate'],
            ] : null,
        ];
    }

    /**
     * @param  list<array{sector_id: int|null, name: string, properties_worked: int, visits: int, interested: int, installations: int, conversion_rate: float}>  $sectors
     * @return list<array{type: string, title: string, detail: string}>
     */
    protected function buildAlerts(AnalyticsFiltersDTO $filters, array $sectors): array
    {
        $alerts = [];

        $idle = $this->repository->sellersWithoutVisitsToday();
        if ($idle !== []) {
            $names = collect($idle)->take(5)->pluck('name')->implode(', ');
            $extra = count($idle) > 5 ? ' +'.(count($idle) - 5) : '';
            $alerts[] = [
                'type' => 'no_visits_today',
                'title' => 'Vendedor sem visitas hoje',
                'detail' => count($idle).' vendedor(es): '.$names.$extra,
            ];
        }

        $pending = $this->repository->countPendingFollowUps(new AnalyticsFiltersDTO(
            user_id: $filters->user_id,
            city_id: $filters->city_id,
            sector_id: $filters->sector_id,
        ));
        if ($pending >= AnalyticsRepository::PENDING_FOLLOW_UPS_ALERT_THRESHOLD) {
            $alerts[] = [
                'type' => 'pending_follow_ups',
                'title' => 'Muitos retornos pendentes',
                'detail' => $pending.' retornos pendentes na agenda (limite de atenção: '
                    .AnalyticsRepository::PENDING_FOLLOW_UPS_ALERT_THRESHOLD.').',
            ];
        }

        foreach ($sectors as $sector) {
            if (
                $sector['visits'] >= AnalyticsRepository::LOW_CONVERSION_MIN_VISITS
                && $sector['conversion_rate'] < AnalyticsRepository::LOW_CONVERSION_MAX_RATE
            ) {
                $alerts[] = [
                    'type' => 'low_conversion_sector',
                    'title' => 'Região com baixa conversão',
                    'detail' => $sector['name'].': '.$sector['conversion_rate'].'% em '
                        .$sector['visits'].' visitas ('.$sector['installations'].' '
                        .mb_strtolower(CommercialTerminology::sales()).').',
                ];
            }
        }

        return $alerts;
    }

    /**
     * @return list<array{type: string, title: string, detail: string}>
     */
    protected function buildSellerAlerts(AnalyticsFiltersDTO $filters): array
    {
        $alerts = [];
        $pending = $this->repository->countPendingFollowUps(new AnalyticsFiltersDTO(
            user_id: $filters->user_id,
        ));

        if ($pending >= AnalyticsRepository::PENDING_FOLLOW_UPS_ALERT_THRESHOLD) {
            $alerts[] = [
                'type' => 'pending_follow_ups',
                'title' => 'Muitos retornos pendentes',
                'detail' => 'Você tem '.$pending.' retornos pendentes na agenda.',
            ];
        }

        return $alerts;
    }
}
