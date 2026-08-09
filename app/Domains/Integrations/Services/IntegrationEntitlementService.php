<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
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

        $flag = $meta['feature_flag'] ?? null;
        if ($flag !== null && ! $this->flags->isEnabled($company, $flag)) {
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
        return $company->latestSubscription()?->plan;
    }

    public function invalidateCompanyCache(int $companyId): void
    {
        Cache::forget($this->mapCacheKey($companyId));
    }

    public function mapCacheKey(int $companyId): string
    {
        return 'integration.map.'.$companyId;
    }
}
