<?php

namespace App\Domains\Billing\Services;

use App\Domains\AI\Models\AIConversation;
use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Billing\Models\UsageRecord;
use App\Domains\Billing\Repositories\BillingRepository;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Communication\Models\Message;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Tenancy\TenantContext;

class UsageService
{
    public function __construct(
        protected BillingRepository $repository,
        protected TenantContext $tenant,
    ) {}

    public function currentPeriod(): string
    {
        return now()->format('Y-m');
    }

    public function liveCount(UsageMetric $metric, ?Company $company = null): int
    {
        $company = $company ?? $this->tenant->company();

        if ($company === null) {
            return 0;
        }

        return match ($metric) {
            UsageMetric::USERS => User::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->count(),
            UsageMetric::PROPERTIES => Property::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->count(),
            UsageMetric::CAMPAIGNS => Campaign::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->count(),
            UsageMetric::MESSAGES => Message::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->count(),
            UsageMetric::AI_TOKENS => (int) AIConversation::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->sum('tokens_used'),
        };
    }

    public function recorded(UsageMetric $metric, ?string $period = null, ?Company $company = null): int
    {
        $company = $company ?? $this->tenant->company();

        if ($company === null) {
            return 0;
        }

        $period ??= $this->currentPeriod();
        $record = $this->repository->usageRecord($company, $metric, $period);

        return (int) ($record?->value ?? 0);
    }

    /**
     * Seat metrics use live counts; consumable metrics prefer recorded ledger when present.
     */
    public function current(UsageMetric $metric, ?string $period = null, ?Company $company = null): int
    {
        $live = $this->liveCount($metric, $company);

        if (in_array($metric, [UsageMetric::USERS, UsageMetric::PROPERTIES, UsageMetric::CAMPAIGNS], true)) {
            return $live;
        }

        $recorded = $this->recorded($metric, $period, $company);

        return max($live, $recorded);
    }

    public function record(
        UsageMetric $metric,
        int $amount = 1,
        ?string $period = null,
        ?Company $company = null,
    ): UsageRecord {
        $company = $company ?? $this->tenant->company();

        if ($company === null) {
            throw new \RuntimeException('Company context is required to record usage.');
        }

        $period ??= $this->currentPeriod();

        return $this->repository->incrementUsage($company, $metric, $period, $amount);
    }

    /**
     * @return list<UsageMetric>
     */
    public function trackedMetrics(): array
    {
        return UsageMetric::cases();
    }
}
