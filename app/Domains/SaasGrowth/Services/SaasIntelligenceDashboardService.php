<?php

namespace App\Domains\SaasGrowth\Services;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Platform\Models\CompanyHealthScore;
use App\Domains\SaasGrowth\DTOs\SaasIntelligenceMetrics;
use App\Domains\SaasGrowth\Enums\HealthClassification;
use Illuminate\Support\Facades\Cache;

class SaasIntelligenceDashboardService
{
    public const CACHE_KEY = 'saas.growth.intelligence.v1';

    public function __construct(
        protected CompanyHealthScoreService $health,
        protected UpgradeIntelligenceService $upgrades,
        protected SaasUsageService $usage,
    ) {}

    public function metrics(): SaasIntelligenceMetrics
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            $total = Company::query()->where('is_system', false)->count();
            $active = Company::query()
                ->where('is_system', false)
                ->where('status', Company::STATUS_ACTIVE)
                ->count();

            $activeTrials = Subscription::query()
                ->withoutGlobalScopes()
                ->where('status', Subscription::STATUS_TRIAL)
                ->count();

            $converted = AuditLog::query()
                ->withoutGlobalScopes()
                ->whereIn('action', ['acquisition.trial.converted', 'trial.converted'])
                ->distinct('auditable_id')
                ->count('auditable_id');

            // Fallback count if SQLite distinct quirks
            if ($converted === 0) {
                $converted = (int) AuditLog::query()
                    ->withoutGlobalScopes()
                    ->whereIn('action', ['acquisition.trial.converted', 'trial.converted'])
                    ->count();
            }

            $trialStarted = max($activeTrials + $converted, 1);
            $conversionRate = round(($converted / $trialStarted) * 100, 2);

            $avgHealth = $this->health->averageScore();
            $atRiskCount = $this->health->countAtRisk();

            $mrr = (float) Subscription::query()
                ->withoutGlobalScopes()
                ->where('subscriptions.status', Subscription::STATUS_ACTIVE)
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->sum('plans.price');

            $atRisk = CompanyHealthScore::query()
                ->with('company')
                ->where(function ($q): void {
                    $q->where('classification', HealthClassification::Risk->value)
                        ->orWhere('score', '<=', 30);
                })
                ->orderBy('score')
                ->limit(10)
                ->get()
                ->map(fn (CompanyHealthScore $row) => [
                    'company_id' => $row->company_id,
                    'name' => $row->company?->name ?? 'Empresa #'.$row->company_id,
                    'score' => $row->score,
                    'classification' => $row->classification ?? HealthClassification::fromScore($row->score)->value,
                ])
                ->all();

            $upgradeHints = [];
            Company::query()
                ->where('is_system', false)
                ->where('status', Company::STATUS_ACTIVE)
                ->limit(25)
                ->get()
                ->each(function (Company $company) use (&$upgradeHints): void {
                    foreach ($this->upgrades->hints($company) as $hint) {
                        $upgradeHints[] = [
                            'company_id' => $company->id,
                            'name' => $company->name,
                            'metric' => $hint['metric'],
                            'percent' => $hint['percent'],
                            'message' => $hint['message'],
                        ];
                    }
                });

            return new SaasIntelligenceMetrics(
                totalCompanies: $total,
                activeCompanies: $active,
                activeTrials: $activeTrials,
                convertedTrials: $converted,
                trialConversionRate: $conversionRate,
                averageHealth: $avgHealth,
                companiesAtRisk: $atRiskCount,
                estimatedMrr: round($mrr, 2),
                atRisk: $atRisk,
                upgradeHints: array_slice($upgradeHints, 0, 15),
            );
        });
    }
}
