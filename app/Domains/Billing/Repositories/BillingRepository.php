<?php

namespace App\Domains\Billing\Repositories;

use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Billing\Models\PlanFeature;
use App\Domains\Billing\Models\UsageRecord;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BillingRepository
{
    public function activeSubscription(Company $company): ?Subscription
    {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIAL,
            ])
            ->with('plan')
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, PlanFeature>
     */
    public function featuresForPlan(Plan $plan): Collection
    {
        return PlanFeature::query()
            ->where('plan_id', $plan->id)
            ->orderBy('feature_key')
            ->get();
    }

    public function featureValue(Plan $plan, UsageMetric|string $metric): ?PlanFeature
    {
        $key = $metric instanceof UsageMetric ? $metric->value : $metric;

        return PlanFeature::query()
            ->where('plan_id', $plan->id)
            ->where('feature_key', $key)
            ->first();
    }

    public function usageRecord(Company $company, UsageMetric $metric, string $period): ?UsageRecord
    {
        return UsageRecord::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('metric', $metric->value)
            ->where('period', $period)
            ->first();
    }

    public function incrementUsage(Company $company, UsageMetric $metric, string $period, int $amount): UsageRecord
    {
        return DB::transaction(function () use ($company, $metric, $period, $amount) {
            $record = UsageRecord::query()
                ->withoutGlobalScopes()
                ->firstOrNew([
                    'company_id' => $company->id,
                    'metric' => $metric->value,
                    'period' => $period,
                ]);

            $record->value = (int) $record->value + max(0, $amount);
            $record->save();

            return $record;
        });
    }

    /**
     * @param  array<string, string|null>  $features
     */
    public function syncPlanFeatures(Plan $plan, array $features): void
    {
        foreach ($features as $key => $value) {
            PlanFeature::query()->updateOrCreate(
                [
                    'plan_id' => $plan->id,
                    'feature_key' => $key,
                ],
                [
                    'feature_value' => $value,
                ]
            );
        }
    }
}
