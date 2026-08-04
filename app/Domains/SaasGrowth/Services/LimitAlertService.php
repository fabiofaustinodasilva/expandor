<?php

namespace App\Domains\SaasGrowth\Services;

use App\Domains\Company\Models\Company;
use App\Domains\SaasGrowth\DTOs\CompanyUsageSnapshot;
use App\Domains\SaasGrowth\Enums\UsageMetric;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Facades\Cache;

class LimitAlertService
{
    public function __construct(
        protected SaasUsageService $usage,
        protected SecurityService $security,
    ) {}

    /**
     * @return list<array{metric: string, percent: float, level: string, message: string, event: string}>
     */
    public function evaluate(Company $company): array
    {
        $snapshot = $this->usage->snapshot($company);
        $alerts = [];

        foreach ([UsageMetric::Users, UsageMetric::Customers, UsageMetric::StorageMb] as $metric) {
            $percent = $snapshot->percent($metric->value);
            if ($percent === null) {
                continue;
            }

            if ($percent >= 90) {
                $alerts[] = $this->emitOnce($company, $metric, $percent, 'reached', [
                    'message' => 'Faça upgrade para continuar crescendo',
                    'event' => 'saas.limit_reached',
                ]);
            } elseif ($percent >= 80) {
                $alerts[] = $this->emitOnce($company, $metric, $percent, 'warning', [
                    'message' => 'Você está próximo do limite do seu plano',
                    'event' => 'saas.limit_warning',
                ]);
            }
        }

        return array_values(array_filter($alerts));
    }

    /**
     * @param  array{message: string, event: string}  $meta
     * @return array{metric: string, percent: float, level: string, message: string, event: string}|null
     */
    protected function emitOnce(Company $company, UsageMetric $metric, float $percent, string $level, array $meta): ?array
    {
        $cacheKey = "saas.limit.{$level}.{$company->id}.{$metric->value}";
        if (Cache::has($cacheKey)) {
            return [
                'metric' => $metric->value,
                'percent' => $percent,
                'level' => $level,
                'message' => $meta['message'],
                'event' => $meta['event'],
            ];
        }

        Cache::put($cacheKey, true, now()->addHours(12));

        $this->security->recordAudit(
            action: $meta['event'],
            user: null,
            auditable: $company,
            newValues: [
                'metric' => $metric->value,
                'percent' => $percent,
                'message' => $meta['message'],
            ],
            companyId: $company->id,
        );

        return [
            'metric' => $metric->value,
            'percent' => $percent,
            'level' => $level,
            'message' => $meta['message'],
            'event' => $meta['event'],
        ];
    }
}
