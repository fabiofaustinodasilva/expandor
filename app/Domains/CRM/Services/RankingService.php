<?php

namespace App\Domains\CRM\Services;

use App\Domains\CRM\Repositories\CrmMetricsRepository;
use App\Domains\CRM\Repositories\OpportunityRepository;

class RankingService
{
    public function __construct(
        protected OpportunityRepository $opportunities,
        protected CrmMetricsRepository $metrics,
    ) {}

    /**
     * @return list<array{user_id: int, name: string, won_amount: float, won_count: int, goal_amount: float, goal_progress: float}>
     */
    public function sellerRanking(?string $from = null, ?string $to = null): array
    {
        $from ??= now()->startOfMonth()->toDateString();
        $to ??= now()->endOfMonth()->toDateString();

        $won = $this->opportunities->wonBySeller($from, $to);
        $goals = $this->metrics->goalsForPeriod($from, $to)->keyBy('user_id');

        $ranking = [];
        foreach ($won as $row) {
            $goalAmount = (float) ($goals->get($row['user_id'])?->target_amount ?? 0);
            $progress = $goalAmount > 0
                ? round(($row['won_amount'] / $goalAmount) * 100, 1)
                : 0.0;

            $ranking[] = [
                'user_id' => $row['user_id'],
                'name' => $row['name'],
                'won_amount' => $row['won_amount'],
                'won_count' => $row['won_count'],
                'goal_amount' => $goalAmount,
                'goal_progress' => $progress,
            ];
        }

        usort($ranking, fn ($a, $b) => $b['won_amount'] <=> $a['won_amount']);

        return $ranking;
    }
}
