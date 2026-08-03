<?php

namespace App\Domains\CRM\Services;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Enums\GoalPeriod;
use App\Domains\CRM\Models\SalesGoal;
use App\Domains\CRM\Repositories\CrmMetricsRepository;
use App\Domains\Security\Services\SecurityService;

class GoalService
{
    public function __construct(
        protected CrmMetricsRepository $repository,
        protected SecurityService $security,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(array $data, ?User $actor = null): SalesGoal
    {
        $period = GoalPeriod::from($data['period_type'] ?? GoalPeriod::MONTHLY->value);
        $start = $data['period_start'];
        $end = $data['period_end'];

        $goal = SalesGoal::query()->updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'period_type' => $period->value,
                'period_start' => $start,
            ],
            [
                'period_end' => $end,
                'target_amount' => $data['target_amount'] ?? 0,
                'target_count' => $data['target_count'] ?? 0,
            ]
        );

        if ($actor) {
            $this->security->recordAudit(
                action: 'crm.sales_goal.upserted',
                user: $actor,
                auditable: $goal,
                newValues: [
                    'user_id' => $goal->user_id,
                    'target_amount' => $goal->target_amount,
                    'period_start' => $goal->period_start?->toDateString(),
                ],
                companyId: $goal->company_id,
            );
        }

        return $goal->fresh('user');
    }
}
