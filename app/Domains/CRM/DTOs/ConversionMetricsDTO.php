<?php

namespace App\Domains\CRM\DTOs;

readonly class ConversionMetricsDTO
{
    /**
     * @param  array<string, int>  $leads_by_status
     * @param  array<string, int>  $opportunities_by_stage
     * @param  list<array{user_id: int, name: string, won_amount: float, won_count: int, goal_amount: float, goal_progress: float}>  $seller_ranking
     */
    public function __construct(
        public int $leads_total,
        public int $leads_qualified,
        public int $leads_converted,
        public float $lead_conversion_rate,
        public int $opportunities_open,
        public int $opportunities_won,
        public int $opportunities_lost,
        public float $opportunity_win_rate,
        public float $pipeline_amount,
        public float $won_amount,
        public int $visit_installations,
        public array $leads_by_status,
        public array $opportunities_by_stage,
        public array $seller_ranking,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'leads_total' => $this->leads_total,
            'leads_qualified' => $this->leads_qualified,
            'leads_converted' => $this->leads_converted,
            'lead_conversion_rate' => $this->lead_conversion_rate,
            'opportunities_open' => $this->opportunities_open,
            'opportunities_won' => $this->opportunities_won,
            'opportunities_lost' => $this->opportunities_lost,
            'opportunity_win_rate' => $this->opportunity_win_rate,
            'pipeline_amount' => $this->pipeline_amount,
            'won_amount' => $this->won_amount,
            'visit_installations' => $this->visit_installations,
            'leads_by_status' => $this->leads_by_status,
            'opportunities_by_stage' => $this->opportunities_by_stage,
            'seller_ranking' => $this->seller_ranking,
        ];
    }
}
