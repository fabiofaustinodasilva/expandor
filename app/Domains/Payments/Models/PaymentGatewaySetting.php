<?php

namespace App\Domains\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySetting extends Model
{
    public const PROVIDER_MERCADOPAGO = 'mercadopago';

    public const MODE_SANDBOX = 'sandbox';

    public const MODE_PRODUCTION = 'production';

    protected $fillable = [
        'provider',
        'mode',
        'public_key',
        'access_token',
        'webhook_url',
        'webhook_secret',
        'active',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_tested_at' => 'datetime',
            'access_token' => 'encrypted',
            'webhook_secret' => 'encrypted',
        ];
    }

    public static function forProvider(string $provider): self
    {
        return static::query()->firstOrCreate(
            ['provider' => $provider],
            [
                'mode' => self::MODE_SANDBOX,
                'active' => false,
                'webhook_url' => url('/webhooks/'.$provider),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toProviderConfig(array $fallback = []): array
    {
        $token = filled($this->access_token) ? $this->access_token : ($fallback['access_token'] ?? null);
        $webhook = filled($this->webhook_secret) ? $this->webhook_secret : ($fallback['webhook_token'] ?? null);

        return array_merge($fallback, [
            'access_token' => $token,
            'webhook_token' => $webhook,
            'public_key' => $this->public_key ?: ($fallback['public_key'] ?? null),
            'mode' => $this->mode ?: self::MODE_SANDBOX,
            'active' => (bool) $this->active,
        ]);
    }
}
