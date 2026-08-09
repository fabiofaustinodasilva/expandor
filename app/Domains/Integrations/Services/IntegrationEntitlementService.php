<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Integrations\Support\IntegrationProviders;
use App\Domains\Platform\Services\FeatureFlagService;
use Illuminate\Support\Facades\Cache;

class IntegrationEntitlementService
{
    public function __construct(
        private readonly FeatureFlagService $flags,
    ) {}

    public function allowsGoogleMaps(Company $company): bool
    {
        return $this->allows($company, IntegrationProviders::GOOGLE_MAPS);
    }

    public function allows(Company $company, string $provider): bool
    {
        $meta = match ($provider) {
            IntegrationProviders::GOOGLE_MAPS => IntegrationProviders::googleMaps(),
            default => null,
        };

        if ($meta === null) {
            return false;
        }

        if (! $this->planAllows($company, $meta['plan_feature'])) {
            return false;
        }

        // PlanCatalog feature is the Super Admin source of truth for plan entitlement.
        // FeatureFlag is an optional platform kill-switch: only enforce when the flag row exists.
        // Missing flag must NOT deny (production may enable plan feature before FeatureFlagSeeder runs).
        $flag = $meta['feature_flag'] ?? null;
        if ($flag !== null && $this->flags->exists($flag) && ! $this->flags->isEnabled($company, $flag)) {
            return false;
        }

        return true;
    }

    public function planAllows(Company $company, ?string $planFeature): bool
    {
        if ($planFeature === null || $planFeature === '') {
            return true;
        }

        $plan = $this->planFor($company);
        if (! $plan instanceof Plan) {
            return false;
        }

        // Strict: require explicit catalog feature (no NavVisibility legacy bypass).
        return $plan->hasCatalogFeature($planFeature);
    }

    public function planFor(Company $company): ?Plan
    {
        // Prefer commercially active subscription (same rule as BillingRepository).
        $active = Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIAL,
            ])
            ->with('plan')
            ->latest('id')
            ->first();

        if ($active?->plan instanceof Plan) {
            return $active->plan;
        }

        return $company->latestSubscription()?->plan;
    }

    public function invalidateCompanyCache(int $companyId): void
    {
        Cache::forget($this->mapCacheKey($companyId));
    }

    /**
     * Clear map-provider resolution cache for every company currently on this plan.
     */
    public function invalidatePlanCompaniesCache(Plan $plan): void
    {
        $companyIds = Subscription::query()
            ->withoutGlobalScopes()
            ->where('plan_id', $plan->id)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIAL,
            ])
            ->pluck('company_id')
            ->unique()
            ->filter();

        foreach ($companyIds as $companyId) {
            $this->invalidateCompanyCache((int) $companyId);
        }
    }

    public function mapCacheKey(int $companyId): string
    {
        return 'integration.map.'.$companyId;
    }
}
