<?php

namespace App\Domains\Integrations\DTOs;

use App\Domains\Integrations\Support\IntegrationProviders;

final class MapProviderDecision
{
    /**
     * @param  array<string, mixed>  $publicConfig  Never include server secrets. Browser key only when authorized + connected.
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
     * Visual map engine for this sprint: always Leaflet until adapter sprint.
     */
    public function visualProvider(): string
    {
        return IntegrationProviders::LEAFLET_OSM;
    }
}
