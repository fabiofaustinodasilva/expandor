<?php

namespace App\Domains\Analytics\DTOs;

readonly class DashboardMetricsDTO
{
    /**
     * @param  array<string, int>  $visits_by_status
     * @param  list<array{date: string, total: int}>  $visits_by_period
     * @param  list<array{user_id: int, name: string, visits: int, interested: int, installations: int, conversion_rate: float}>  $seller_productivity
     * @param  list<array{sector_id: int|null, name: string, properties_worked: int, visits: int, interested: int, installations: int, conversion_rate: float}>  $sector_performance
     * @param  list<array{user_id: int, name: string, visits: int, interested: int, installations: int, conversion_rate: float}>  $seller_ranking
     * @param  array{points: int, visits: int, interested: int, contracts: int, pct_visits: float, pct_interested: float, pct_contracts: float}  $funnel
     * @param  array{active_sellers: int, avg_visits: float, avg_contracts: float, best_seller: ?array{user_id: int, name: string, installations: int, conversion_rate: float}}  $productivity
     * @param  list<array{type: string, title: string, detail: string}>  $alerts
     */
    public function __construct(
        public int $properties_total,
        public int $visits_total,
        public int $interested_total,
        public int $installations_total,
        public float $conversion_rate,
        public int $pending_follow_ups,
        public array $visits_by_status,
        public array $visits_by_period,
        public array $seller_productivity,
        public array $sector_performance,
        public array $seller_ranking,
        public array $funnel,
        public array $productivity,
        public array $alerts,
        public bool $team_view,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'properties_total' => $this->properties_total,
            'visits_total' => $this->visits_total,
            'interested_total' => $this->interested_total,
            'installations_total' => $this->installations_total,
            'conversion_rate' => $this->conversion_rate,
            'pending_follow_ups' => $this->pending_follow_ups,
            'visits_by_status' => $this->visits_by_status,
            'visits_by_period' => $this->visits_by_period,
            'seller_productivity' => $this->seller_productivity,
            'sector_performance' => $this->sector_performance,
            'seller_ranking' => $this->seller_ranking,
            'funnel' => $this->funnel,
            'productivity' => $this->productivity,
            'alerts' => $this->alerts,
            'team_view' => $this->team_view,
        ];
    }
}
