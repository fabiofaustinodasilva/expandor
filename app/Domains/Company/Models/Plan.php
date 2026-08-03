<?php

namespace App\Domains\Company\Models;

use App\Domains\Billing\Models\PlanFeature;
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
        'max_users',
        'max_properties',
        'max_campaigns',
        'features',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
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
}
