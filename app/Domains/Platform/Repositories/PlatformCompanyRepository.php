<?php

namespace App\Domains\Platform\Repositories;

use App\Domains\AI\Models\AIConversation;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\Communication\Enums\MessageStatus;
use App\Domains\Communication\Models\Message;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Services\BillingAutomationService;
use App\Domains\Onboarding\Services\OnboardingService;
use App\Domains\Platform\DTOs\PlatformDashboardMetrics;
use App\Domains\Platform\Enums\HealthRiskLevel;
use App\Domains\Platform\Repositories\PlatformConsoleRepository;
use App\Domains\Sales\Properties\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlatformCompanyRepository
{
    public function paginateClients(int $perPage = 20): LengthAwarePaginator
    {
        $paginator = Company::query()
            ->where('is_system', false)
            ->latest('id')
            ->paginate($perPage);

        $paginator->getCollection()->load([
            'subscriptions' => fn ($query) => $query
                ->withoutGlobalScopes()
                ->with('plan')
                ->orderByDesc('id'),
        ]);

        return $paginator;
    }

    public function metrics(): PlatformDashboardMetrics
    {
        $clientCompanyIds = Company::query()
            ->where('is_system', false)
            ->pluck('id');

        $revenue = app(BillingAutomationService::class)->platformRevenueMetrics();
        $onboarding = app(OnboardingService::class)->platformMetrics();
        $saasActivation = app(\App\Domains\Onboarding\Services\SaasOnboardingService::class)->activationMetrics();
        $console = app(PlatformConsoleRepository::class);

        $totalCompanies = Company::query()->where('is_system', false)->count();
        $cancelledClients = Company::query()
            ->where('is_system', false)
            ->where('status', Company::STATUS_CANCELLED)
            ->count();
        $churnRate = round($cancelledClients / max($totalCompanies, 1) * 100, 1);

        $trial = (int) $revenue['trial_clients'];
        $converted = AuditLog::query()
            ->withoutGlobalScopes()
            ->where('action', 'acquisition.trial.converted')
            ->count();
        $trialConversionRate = ($converted + $trial) > 0
            ? round($converted / ($converted + $trial) * 100, 1)
            : 0.0;

        return new PlatformDashboardMetrics(
            activeCompanies: Company::query()
                ->where('is_system', false)
                ->where('status', Company::STATUS_ACTIVE)
                ->count(),
            totalUsers: User::query()
                ->withoutGlobalScopes()
                ->whereIn('company_id', $clientCompanyIds)
                ->count(),
            totalProperties: Property::query()
                ->withoutGlobalScopes()
                ->whereIn('company_id', $clientCompanyIds)
                ->count(),
            aiTokensConsumed: (int) AIConversation::query()
                ->withoutGlobalScopes()
                ->whereIn('company_id', $clientCompanyIds)
                ->sum('tokens_used'),
            messagesSent: Message::query()
                ->withoutGlobalScopes()
                ->whereIn('company_id', $clientCompanyIds)
                ->where('status', MessageStatus::SENT->value)
                ->count(),
            mrr: (float) $revenue['mrr'],
            arr: (float) $revenue['arr'],
            monthlyRevenue: (float) $revenue['monthly_revenue'],
            yearlyRevenue: (float) $revenue['yearly_revenue'],
            trialClients: $trial,
            suspendedClients: (int) $revenue['suspended_clients'],
            pastDueClients: (int) $revenue['past_due_clients'],
            clientsByPlan: collect($revenue['clients_by_plan'])->map(fn ($item) => [
                'count' => (int) $item['count'],
                'mrr' => (float) $item['mrr'],
            ])->all(),
            revenueByPlan: collect($revenue['revenue_by_plan'])->map(fn ($item) => [
                'count' => (int) $item['count'],
                'mrr' => (float) $item['mrr'],
            ])->all(),
            onboardingInProgress: (int) $onboarding['onboarding_in_progress'],
            onboardingCompleted: (int) $onboarding['onboarding_completed'],
            onboardingAvgHours: $onboarding['onboarding_avg_hours'],
            onboardingStuck: (int) $onboarding['onboarding_stuck'],
            onboardingCompletionRate: (float) $onboarding['onboarding_completion_rate'],
            saasOnboardingStarted: (int) $saasActivation['onboarding_started'],
            saasOnboardingCompleted: (int) $saasActivation['onboarding_completed'],
            activationRate: (float) $saasActivation['activation_rate'],
            averageActivationTimeHours: $saasActivation['average_activation_time_hours'],
            averageHealthScore: $console->averageHealthScore(),
            healthyCompanies: $console->countByRisk(HealthRiskLevel::Healthy),
            atRiskCompanies: $console->countByRisk(HealthRiskLevel::AtRisk) + $console->countByRisk(HealthRiskLevel::Medium),
            criticalCompanies: $console->countByRisk(HealthRiskLevel::Critical),
            activeImpersonations: $console->activeImpersonationsCount(),
            totalCompanies: $totalCompanies,
            cancelledClients: $cancelledClients,
            churnRate: $churnRate,
            trialConversionRate: $trialConversionRate,
        );
    }
}
