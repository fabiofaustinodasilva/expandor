<?php

namespace App\Domains\SaasGrowth\Services;

use App\Domains\Company\Models\Company;
use App\Domains\SaasGrowth\Enums\UsageMetric;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Facades\Cache;

class UpgradeIntelligenceService
{
    public function __construct(
        protected SaasUsageService $usage,
        protected SecurityService $security,
    ) {}

    /**
     * @return list<array{metric: string, value: int, limit: int, percent: float, message: string}>
     */
    public function hints(Company $company, bool $emitEvents = false): array
    {
        $snapshot = $this->usage->snapshot($company, persist: false);
        $hints = [];

        foreach ([UsageMetric::Users, UsageMetric::Customers, UsageMetric::StorageMb] as $metric) {
            $row = $snapshot->metrics[$metric->value] ?? null;
            if ($row === null || $row['limit'] === null || $row['percent'] === null) {
                continue;
            }

            if ($row['percent'] < 80) {
                continue;
            }

            $message = sprintf(
                'Seu crescimento está acelerado (%d/%d %s). Recomendamos upgrade.',
                $row['value'],
                $row['limit'],
                $metric->label()
            );

            $hints[] = [
                'metric' => $metric->value,
                'value' => $row['value'],
                'limit' => $row['limit'],
                'percent' => $row['percent'],
                'message' => $message,
            ];

            if ($emitEvents) {
                $this->emitOnce($company, $metric->value, $message, $row);
            }
        }

        return $hints;
    }

    /**
     * @return list<array{metric: string, value: int, limit: int, percent: float, message: string}>
     */
    public function recommendations(Company $company): array
    {
        return $this->hints($company, emitEvents: true);
    }

    /**
     * @param  array{value: int, limit: int|null, percent: float|null}  $row
     */
    protected function emitOnce(Company $company, string $metric, string $message, array $row): void
    {
        $key = "saas.upgrade_recommended.{$company->id}.{$metric}";
        if (Cache::has($key)) {
            return;
        }
        Cache::put($key, true, now()->addDay());

        $this->security->recordAudit(
            action: 'subscription.upgrade_recommended',
            user: null,
            auditable: $company,
            newValues: [
                'metric' => $metric,
                'value' => $row['value'],
                'limit' => $row['limit'],
                'percent' => $row['percent'],
                'message' => $message,
            ],
            companyId: $company->id,
        );

        $this->security->recordAudit(
            action: 'upgrade_recommended',
            user: null,
            auditable: $company,
            newValues: ['metric' => $metric, 'message' => $message],
            companyId: $company->id,
        );
    }
}
