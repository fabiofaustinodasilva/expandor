<?php

namespace App\Domains\CRM\Actions;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\SalesGoal;
use App\Domains\CRM\Services\GoalService;

class UpsertSalesGoalAction
{
    public function __construct(protected GoalService $goals) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?User $actor = null): SalesGoal
    {
        return $this->goals->upsert($data, $actor);
    }
}
