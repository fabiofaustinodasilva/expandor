<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\DTOs\LimitCheckResult;
use App\Domains\Billing\DTOs\MetricUsageDTO;
use App\Domains\Billing\DTOs\PlanOverviewDTO;
use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Billing\Exceptions\PlanLimitExceededException;
use App\Domains\Billing\Models\UsageRecord;
use App\Domains\Billing\Repositories\BillingRepository;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Tenancy\TenantContext;
use Illuminate\Support\Collection;

class BillingService
{
    public function __construct(
        protected BillingRepository $repository,
        protected UsageService $usage,
        protected TenantContext $tenant,
    ) {}

    public function currentPlan(?Company $company = null): ?Plan
    {
        $company = $company ?? $this->tenant->company();

        if ($company === null) {
            return null;
        }

        return $this->repository->activeSubscription($company)?->plan;
    }

    /**
     * @return array<string, string|null>
     */
    public function planResources(?Plan $plan = null, ?Company $company = null): array
    {
        $plan ??= $this->currentPlan($company);

        if ($plan === null) {
            return [];
        }

        $features = $this->repository->featuresForPlan($plan);

        if ($features->isEmpty()) {
            return $this->fallbackResourcesFromPlanColumns($plan);
        }

        return $features
            ->mapWithKeys(fn ($feature) => [$feature->feature_key => $feature->feature_value])
            ->all();
    }

    public function limitFor(UsageMetric $metric, ?Company $company = null): ?int
    {
        $plan = $this->currentPlan($company);

        if ($plan === null) {
            return 0;
        }

        $feature = $this->repository->featureValue($plan, $metric);

        if ($feature !== null) {
            return $feature->numericLimit();
        }

        return $this->fallbackLimitFromPlanColumns($plan, $metric);
    }

    public function checkLimit(
        UsageMetric $metric,
        int $additional = 1,
        ?Company $company = null,
    ): LimitCheckResult {
        $company = $company ?? $this->tenant->company();
        $current = $this->usage->current($metric, company: $company);
        $limit = $this->limitFor($metric, $company);

        if ($limit === null) {
            return new LimitCheckResult(
                metric: $metric,
                allowed: true,
                current: $current,
                limit: null,
                remaining: null,
            );
        }

        $projected = $current + max(0, $additional);
        $allowed = $projected <= $limit;
        $remaining = max(0, $limit - $current);

        return new LimitCheckResult(
            metric: $metric,
            allowed: $allowed,
            current: $current,
            limit: $limit,
            remaining: $remaining,
            message: $allowed
                ? null
                : "Seu plano atual permite {$limit} {$metric->label()}. Faça upgrade para continuar.",
        );
    }

    public function assertWithinLimit(
        UsageMetric $metric,
        int $additional = 1,
        ?Company $company = null,
    ): LimitCheckResult {
        $result = $this->checkLimit($metric, $additional, $company);

        if (! $result->allowed) {
            throw new PlanLimitExceededException($result);
        }

        return $result;
    }

    public function registerConsumption(
        UsageMetric $metric,
        int $amount = 1,
        ?string $period = null,
        ?Company $company = null,
    ): UsageRecord {
        return $this->usage->record($metric, $amount, $period, $company);
    }

    public function overview(?Company $company = null): PlanOverviewDTO
    {
        $company = $company ?? $this->tenant->company();

        if ($company === null) {
            return new PlanOverviewDTO(
                plan: null,
                subscription: null,
                metrics: collect(),
                features: [],
                period: $this->usage->currentPeriod(),
            );
        }

        $subscription = $this->repository->activeSubscription($company);
        $plan = $subscription?->plan;
        $period = $this->usage->currentPeriod();
        $features = $this->planResources($plan, $company);

        $metrics = Collection::make(UsageMetric::cases())
            ->map(fn (UsageMetric $metric) => new MetricUsageDTO(
                metric: $metric,
                current: $this->usage->current($metric, $period, $company),
                limit: $this->limitFor($metric, $company),
                recorded: $this->usage->recorded($metric, $period, $company),
                period: $period,
            ));

        return new PlanOverviewDTO(
            plan: $plan,
            subscription: $subscription,
            metrics: $metrics,
            features: $features,
            period: $period,
        );
    }

    /**
     * @return array<string, string|null>
     */
    protected function fallbackResourcesFromPlanColumns(Plan $plan): array
    {
        return [
            UsageMetric::USERS->value => $plan->max_users === null ? 'unlimited' : (string) $plan->max_users,
            UsageMetric::PROPERTIES->value => $plan->max_properties === null ? 'unlimited' : (string) $plan->max_properties,
            UsageMetric::CAMPAIGNS->value => $plan->max_campaigns === null ? 'unlimited' : (string) $plan->max_campaigns,
            UsageMetric::MESSAGES->value => null,
            UsageMetric::AI_TOKENS->value => null,
        ];
    }

    protected function fallbackLimitFromPlanColumns(Plan $plan, UsageMetric $metric): ?int
    {
        return match ($metric) {
            UsageMetric::USERS => $plan->max_users,
            UsageMetric::PROPERTIES => $plan->max_properties,
            UsageMetric::CAMPAIGNS => $plan->max_campaigns,
            UsageMetric::MESSAGES, UsageMetric::AI_TOKENS => null,
        };
    }
}
