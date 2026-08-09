<?php

namespace App\Domains\Integrations\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Integrations\Enums\CompanyIntegrationStatus;
use App\Domains\Integrations\Support\IntegrationProviders;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyIntegration extends Model
{
    use BelongsToTenant;

    protected $table = 'company_integrations';

    protected $fillable = [
        'company_id',
        'provider',
        'category',
        'enabled',
        'status',
        'configuration',
        'credentials',
        'last_tested_at',
        'last_error',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'status' => CompanyIntegrationStatus::class,
            'configuration' => 'array',
            'credentials' => 'encrypted:array',
            'last_tested_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isGoogleMaps(): bool
    {
        return $this->provider === IntegrationProviders::GOOGLE_MAPS;
    }

    public function browserApiKey(): ?string
    {
        $key = $this->credentials['browser_api_key'] ?? null;

        return is_string($key) && $key !== '' ? $key : null;
    }

    public function hasBrowserApiKey(): bool
    {
        return $this->browserApiKey() !== null;
    }

    public function maskedBrowserApiKey(): ?string
    {
        $key = $this->browserApiKey();
        if ($key === null) {
            return null;
        }

        $suffix = substr($key, -4);

        return str_repeat('•', max(12, strlen($key) - 4)).$suffix;
    }

    public function isUsable(): bool
    {
        return $this->enabled
            && $this->status === CompanyIntegrationStatus::Connected
            && $this->hasBrowserApiKey();
    }
}
