<?php

namespace App\Domains\Integrations\DTOs;

use App\Domains\Integrations\Support\IntegrationProviders;

/**
 * Public map config for the authenticated tenant's browser session.
 * May include browser API key only when entitled + connected.
 * Never include server secrets.
 */
final class MapFrontendConfig
{
    /**
     * @param  array{browserKey?: string, mapId?: string}|null  $google
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $fallback,
        public readonly string $reason,
        public readonly bool $entitled,
        public readonly bool $configured,
        public readonly ?array $google = null,
        public readonly bool $forceFailure = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'provider' => $this->provider,
            'fallback' => $this->fallback,
            'reason' => $this->reason,
            'entitled' => $this->entitled,
            'configured' => $this->configured,
        ];

        if ($this->google !== null) {
            $payload['google'] = $this->google;
        }

        if ($this->forceFailure) {
            $payload['forceFailure'] = true;
        }

        return $payload;
    }

    public function usesGoogleVisual(): bool
    {
        return $this->provider === IntegrationProviders::GOOGLE_MAPS
            && is_array($this->google)
            && filled($this->google['browserKey'] ?? null);
    }

    public function browserKey(): ?string
    {
        $key = $this->google['browserKey'] ?? null;

        return is_string($key) && $key !== '' ? $key : null;
    }
}
