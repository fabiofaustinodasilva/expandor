<?php

namespace App\Domains\SaasGrowth\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\Platform\Services\CompanyOperationalMetricsService;
use App\Domains\SaasGrowth\DTOs\CompanyUsageSnapshot;
use App\Domains\SaasGrowth\Enums\UsageMetric;
use App\Domains\SaasGrowth\Models\CompanyUsageMetric;
use Illuminate\Support\Facades\DB;

class SaasUsageService
{
    public function __construct(
        protected CompanyOperationalMetricsService $ops,
    ) {}

    public function snapshot(Company $company, bool $persist = true): CompanyUsageSnapshot
    {
        $plan = $this->currentPlan($company);
        $ops = $this->ops->for($company);

        $users = User::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $customers = Lead::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $deals = Opportunity::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $storage = (int) round($ops->storageUsedMb);

        $metrics = [
            UsageMetric::Users->value => $this->row($users, $plan?->usersLimit()),
            UsageMetric::Customers->value => $this->row($customers, $plan?->customersLimit()),
            UsageMetric::Deals->value => $this->row($deals, null),
            UsageMetric::StorageMb->value => $this->row($storage, $plan?->storageLimit()),
        ];

        if ($persist) {
            $this->persist($company, $metrics);
        }

        return new CompanyUsageSnapshot(
            companyId: $company->id,
            metrics: $metrics,
            planName: $plan?->name,
        );
    }

    /**
     * @return array{value: int, limit: int|null, percent: float|null}
     */
    public function metricUsage(Company $company, UsageMetric $metric): array
    {
        $snap = $this->snapshot($company, persist: false);

        return $snap->metrics[$metric->value] ?? ['value' => 0, 'limit' => null, 'percent' => null];
    }

    public function usagePercent(Company $company, UsageMetric $metric): ?float
    {
        return $this->metricUsage($company, $metric)['percent'];
    }

    public function currentPlan(Company $company): ?Plan
    {
        $sub = $company->latestSubscription();
        if ($sub === null) {
            $sub = Subscription::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->latest('id')
                ->first();
        }

        return $sub?->plan;
    }

    /**
     * @param  array<string, array{value: int, limit: int|null, percent: float|null}>  $metrics
     */
    protected function persist(Company $company, array $metrics): void
    {
        $now = now()->startOfMinute();

        DB::transaction(function () use ($company, $metrics, $now): void {
            foreach ($metrics as $metric => $row) {
                CompanyUsageMetric::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'metric' => $metric,
                        'recorded_at' => $now,
                    ],
                    ['value' => $row['value']],
                );
            }
        });
    }

    /**
     * @return array{value: int, limit: int|null, percent: float|null}
     */
    protected function row(int $value, ?int $limit): array
    {
        $limit = $limit !== null && $limit > 0 ? $limit : null;
        $percent = $limit !== null ? round(($value / $limit) * 100, 2) : null;

        return [
            'value' => $value,
            'limit' => $limit,
            'percent' => $percent,
        ];
    }
}
