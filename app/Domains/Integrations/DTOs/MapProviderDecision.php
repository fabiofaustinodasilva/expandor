<?php

namespace App\Domains\Integrations\DTOs;

use App\Domains\Integrations\Support\IntegrationProviders;

final class MapProviderDecision
{
    /**
     * @param  array<string, mixed>  $publicConfig  Never include secrets. Browser key is attached later via MapFrontendConfigBuilder.
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $reason,
        public readonly bool $entitled,
        public readonly bool $configured,
        public readonly array $publicConfig = [],
    ) {}

    public function usesGoogleMaps(): bool
    {
        return $this->provider === IntegrationProviders::GOOGLE_MAPS;
    }

    public function usesDefaultLeaflet(): bool
    {
        return $this->provider === IntegrationProviders::LEAFLET_OSM;
    }

    /**
     * Visual basemap provider for the map page (Leaflet host + GoogleMutant when google_maps).
     */
    public function visualProvider(): string
    {
        return $this->provider;
    }
}
