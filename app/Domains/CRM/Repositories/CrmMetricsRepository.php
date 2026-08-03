<?php

namespace App\Domains\CRM\Repositories;

use App\Domains\CRM\Models\CommissionEntry;
use App\Domains\CRM\Models\CommissionRule;
use App\Domains\CRM\Models\SalesGoal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CrmMetricsRepository
{
    public function goalsForPeriod(string $periodStart, string $periodEnd): Collection
    {
        return SalesGoal::query()
            ->with('user:id,name')
            ->whereDate('period_start', '<=', $periodEnd)
            ->whereDate('period_end', '>=', $periodStart)
            ->orderBy('user_id')
            ->get();
    }

    public function paginateGoals(int $perPage = 20): LengthAwarePaginator
    {
        return SalesGoal::query()
            ->with('user:id,name')
            ->orderByDesc('period_start')
            ->paginate($perPage);
    }

    public function activeCommissionRule(): ?CommissionRule
    {
        return CommissionRule::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();
    }

    public function paginateCommissionRules(int $perPage = 20): LengthAwarePaginator
    {
        return CommissionRule::query()->orderByDesc('id')->paginate($perPage);
    }

    public function paginateCommissionEntries(int $perPage = 20): LengthAwarePaginator
    {
        return CommissionEntry::query()
            ->with(['user:id,name', 'opportunity:id,title,amount', 'rule:id,name'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
