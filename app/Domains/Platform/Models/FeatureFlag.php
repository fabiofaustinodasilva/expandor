<?php

namespace App\Domains\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'default_enabled',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\FeatureFlagFactory::new();
    }

    public function companyFlags(): HasMany
    {
        return $this->hasMany(CompanyFeatureFlag::class);
    }
}
