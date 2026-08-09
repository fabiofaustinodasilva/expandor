<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Integrations\DTOs\MapFrontendConfig;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Support\IntegrationProviders;

/**
 * Builds tenant-scoped frontend map config.
 * Browser key is read live (not from cache) and only for the current company.
 */
class MapFrontendConfigBuilder
{
    public function __construct(
        private readonly MapIntegrationResolver $resolver,
    ) {}

    public function forCompany(?Company $company, bool $forceFailure = false): MapFrontendConfig
    {
        if (! $company instanceof Company) {
            return new MapFrontendConfig(
                provider: IntegrationProviders::LEAFLET_OSM,
                fallback: IntegrationProviders::LEAFLET_OSM,
                reason: 'no_company',
                entitled: false,
                configured: false,
                forceFailure: false,
            );
        }

        $decision = $this->resolver->resolve($company);

        if (! $decision->usesGoogleMaps()) {
            return new MapFrontendConfig(
                provider: IntegrationProviders::LEAFLET_OSM,
                fallback: IntegrationProviders::LEAFLET_OSM,
                reason: $decision->reason,
                entitled: $decision->entitled,
                configured: $decision->configured,
                forceFailure: false,
            );
        }

        $integration = CompanyIntegration::query()
            ->where('company_id', $company->id)
            ->where('provider', IntegrationProviders::GOOGLE_MAPS)
            ->first();

        $browserKey = $integration?->browserApiKey();

        if ($browserKey === null) {
            return new MapFrontendConfig(
                provider: IntegrationProviders::LEAFLET_OSM,
                fallback: IntegrationProviders::LEAFLET_OSM,
                reason: 'missing_key_live',
                entitled: true,
                configured: false,
                forceFailure: false,
            );
        }

        return new MapFrontendConfig(
            provider: IntegrationProviders::GOOGLE_MAPS,
            fallback: IntegrationProviders::LEAFLET_OSM,
            reason: $decision->reason,
            entitled: true,
            configured: true,
            google: [
                'browserKey' => $browserKey,
            ],
            forceFailure: $forceFailure,
        );
    }
}
