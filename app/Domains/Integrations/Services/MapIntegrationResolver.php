<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Integrations\DTOs\MapProviderDecision;
use App\Domains\Integrations\Enums\CompanyIntegrationStatus;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Support\IntegrationProviders;
use Illuminate\Support\Facades\Cache;

class MapIntegrationResolver
{
    public function __construct(
        private readonly IntegrationEntitlementService $entitlements,
    ) {}

    public function resolve(?Company $company): MapProviderDecision
    {
        if (! $company instanceof Company) {
            return $this->leaflet('no_company');
        }

        return Cache::remember(
            $this->entitlements->mapCacheKey((int) $company->id),
            now()->addMinutes(5),
            fn (): MapProviderDecision => $this->resolveFresh($company),
        );
    }

    public function resolveFresh(Company $company): MapProviderDecision
    {
        if (! $this->entitlements->allowsGoogleMaps($company)) {
            return $this->leaflet('not_entitled', entitled: false);
        }

        $integration = CompanyIntegration::query()
            ->where('company_id', $company->id)
            ->where('provider', IntegrationProviders::GOOGLE_MAPS)
            ->first();

        if (! $integration instanceof CompanyIntegration) {
            return $this->leaflet('not_configured', entitled: true);
        }

        if (! $integration->enabled) {
            return $this->leaflet('disabled', entitled: true, configured: $integration->hasBrowserApiKey());
        }

        if ($integration->status !== CompanyIntegrationStatus::Connected) {
            return $this->leaflet('status_'.$integration->status->value, entitled: true, configured: true);
        }

        if (! $integration->hasBrowserApiKey()) {
            return $this->leaflet('missing_key', entitled: true, configured: false);
        }

        return new MapProviderDecision(
            provider: IntegrationProviders::GOOGLE_MAPS,
            reason: 'connected',
            entitled: true,
            configured: true,
            publicConfig: [
                'provider' => IntegrationProviders::GOOGLE_MAPS,
                'fallback' => IntegrationProviders::LEAFLET_OSM,
                // Browser key is attached live by MapFrontendConfigBuilder (not cached).
            ],
        );
    }

    public function forget(Company|int $company): void
    {
        $id = $company instanceof Company ? (int) $company->id : $company;
        $this->entitlements->invalidateCompanyCache($id);
    }

    private function leaflet(string $reason, bool $entitled = false, bool $configured = false): MapProviderDecision
    {
        return new MapProviderDecision(
            provider: IntegrationProviders::LEAFLET_OSM,
            reason: $reason,
            entitled: $entitled,
            configured: $configured,
            publicConfig: [
                'provider' => IntegrationProviders::LEAFLET_OSM,
            ],
        );
    }
}
