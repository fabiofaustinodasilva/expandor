<?php

namespace App\Domains\Communication\Providers;

use App\Domains\Communication\Models\WhatsAppConnection;
use InvalidArgumentException;

class ProviderFactory
{
    public function make(?WhatsAppConnection $connection = null): WhatsAppProviderContract
    {
        $driver = $connection?->provider
            ?: (string) config('whatsapp.default', 'wppconnect');

        $driver = strtolower(trim($driver));

        return match ($driver) {
            'wppconnect' => $this->makeWppConnect($connection),
            default => throw new InvalidArgumentException("Unsupported WhatsApp provider [{$driver}]."),
        };
    }

    protected function makeWppConnect(?WhatsAppConnection $connection): WppConnectProvider
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('whatsapp.providers.wppconnect', []);

        /** @var array<string, mixed> $credentials */
        $credentials = (array) ($connection?->credentials ?? []);

        return new WppConnectProvider($config, $credentials, $connection);
    }
}
