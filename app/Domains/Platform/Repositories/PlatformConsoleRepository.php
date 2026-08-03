<?php

namespace App\Domains\Platform\Repositories;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Models\OnboardingRun;
use App\Domains\Platform\Enums\HealthRiskLevel;
use App\Domains\Platform\Models\CompanyFeatureFlag;
use App\Domains\Platform\Models\CompanyHealthScore;
use App\Domains\Platform\Models\FeatureFlag;
use App\Domains\Platform\Models\ImpersonationSession;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PlatformConsoleRepository
{
    /**
     * @return Collection<int, FeatureFlag>
     */
    public function activeFlags(): Collection
    {
        return FeatureFlag::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function findFlagByKey(string $key): ?FeatureFlag
    {
        return FeatureFlag::query()->where('key', $key)->where('is_active', true)->first();
    }

    public function companyOverride(Company $company, FeatureFlag $flag): ?CompanyFeatureFlag
    {
        return CompanyFeatureFlag::query()
            ->where('company_id', $company->id)
            ->where('feature_flag_id', $flag->id)
            ->first();
    }

    /**
     * @return Collection<int, CompanyFeatureFlag>
     */
    public function overridesForCompany(Company $company): Collection
    {
        return CompanyFeatureFlag::query()
            ->where('company_id', $company->id)
            ->with('featureFlag')
            ->get();
    }

    public function findClientCompany(int $companyId): ?Company
    {
        return Company::query()
            ->where('id', $companyId)
            ->where('is_system', false)
            ->first();
    }

    public function paginateClients(
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null,
        ?string $subscriptionStatus = null,
    ): LengthAwarePaginator {
        $paginator = Company::query()
            ->where('is_system', false)
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('document', 'like', '%'.$search.'%')
                        ->orWhere('whatsapp', 'like', '%'.$search.'%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($subscriptionStatus, function ($query) use ($subscriptionStatus): void {
                $query->whereHas('subscriptions', function ($sub) use ($subscriptionStatus): void {
                    $sub->withoutGlobalScopes()
                        ->where('status', $subscriptionStatus)
                        ->whereIn('id', function ($latest) {
                            $latest->selectRaw('MAX(id)')
                                ->from('subscriptions')
                                ->groupBy('company_id');
                        });
                });
            })
            ->latest('id')
            ->paginate($perPage);

        $paginator->getCollection()->load([
            'subscriptions' => fn ($query) => $query
                ->withoutGlobalScopes()
                ->with('plan')
                ->orderByDesc('id'),
            'brand',
            'users' => fn ($query) => $query->withoutGlobalScopes()->orderBy('id'),
        ]);

        $companyIds = $paginator->getCollection()->pluck('id');
        $scores = CompanyHealthScore::query()
            ->whereIn('company_id', $companyIds)
            ->get()
            ->keyBy('company_id');

        $paginator->getCollection()->each(function (Company $company) use ($scores): void {
            $company->setRelation('healthScore', $scores->get($company->id));
        });

        return $paginator;
    }

    public function activeImpersonationForAdmin(User $admin): ?ImpersonationSession
    {
        return ImpersonationSession::query()
            ->where('platform_admin_id', $admin->id)
            ->whereNull('ended_at')
            ->latest('id')
            ->first();
    }

    public function findImpersonation(int $id): ?ImpersonationSession
    {
        return ImpersonationSession::query()->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function healthFactors(Company $company): array
    {
        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with('plan')
            ->latest('id')
            ->first();

        $usersCount = User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->count();

        $propertiesCount = Property::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->count();

        $visitsLast30d = Visit::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('visited_at', '>=', now()->subDays(30))
            ->count();

        $onboarding = OnboardingRun::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();

        $lastLoginAt = User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->max('last_login_at');

        return [
            'company_status' => $company->status,
            'subscription_status' => $subscription?->status,
            'plan_slug' => $subscription?->plan?->slug,
            'past_due' => $subscription?->status === Subscription::STATUS_PAST_DUE,
            'users_count' => $usersCount,
            'properties_count' => $propertiesCount,
            'visits_last_30d' => $visitsLast30d,
            'onboarding_percent' => (int) ($onboarding?->percent ?? 0),
            'last_login_at' => $lastLoginAt,
        ];
    }

    public function upsertHealthScore(Company $company, int $score, array $factors): CompanyHealthScore
    {
        return CompanyHealthScore::query()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'score' => $score,
                'risk_level' => HealthRiskLevel::fromScore($score)->value,
                'factors' => $factors,
                'calculated_at' => now(),
            ]
        );
    }

    public function averageHealthScore(): ?float
    {
        $avg = CompanyHealthScore::query()->avg('score');

        return $avg === null ? null : round((float) $avg, 1);
    }

    public function countByRisk(HealthRiskLevel $level): int
    {
        return CompanyHealthScore::query()->where('risk_level', $level->value)->count();
    }

    public function activeImpersonationsCount(): int
    {
        return ImpersonationSession::query()->whereNull('ended_at')->count();
    }
}
