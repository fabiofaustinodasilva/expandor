<?php

namespace App\Domains\Company\Models;

use App\Domains\Billing\Models\PlanFeature;
use App\Domains\Platform\Support\PlanCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected static function newFactory(): \Database\Factories\PlanFactory
    {
        return \Database\Factories\PlanFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'price_yearly',
        'trial_days',
        'max_users',
        'users_limit',
        'max_properties',
        'customers_limit',
        'max_campaigns',
        'max_teams',
        'max_products',
        'max_storage_mb',
        'storage_limit',
        'max_visits',
        'features',
        'status',
        'active',
        'display_order',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'trial_days' => 'integer',
            'max_visits' => 'integer',
            'users_limit' => 'integer',
            'customers_limit' => 'integer',
            'storage_limit' => 'integer',
            'display_order' => 'integer',
            'is_featured' => 'boolean',
            'active' => 'boolean',
            'features' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * @return array<string, bool>
     */
    public function featureMap(): array
    {
        return PlanCatalog::normalizeFeatures($this->features);
    }

    public function hasCatalogFeature(string $key): bool
    {
        return $this->featureMap()[$key] ?? false;
    }

    public function yearlyPrice(): float
    {
        if ($this->price_yearly !== null) {
            return (float) $this->price_yearly;
        }

        return round((float) $this->price * 12 * 0.9, 2);
    }

    public function usersLimit(): int
    {
        return (int) ($this->users_limit ?? $this->max_users ?? 0);
    }

    public function customersLimit(): int
    {
        return (int) ($this->customers_limit ?? $this->max_properties ?? 0);
    }

    public function storageLimit(): int
    {
        return (int) ($this->storage_limit ?? $this->max_storage_mb ?? 0);
    }

    public function isActivePlan(): bool
    {
        if ($this->active !== null) {
            return (bool) $this->active;
        }

        return $this->status === self::STATUS_ACTIVE;
    }
}
