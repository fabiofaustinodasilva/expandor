<?php

namespace App\Domains\Sales\Territory\Models;

use App\Domains\Company\Models\Company;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'state',
        'ibge_code',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function newFactory(): CityFactory
    {
        return CityFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sectors(): HasMany
    {
        return $this->hasMany(Sector::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(\App\Domains\Sales\Properties\Models\Address::class);
    }
}
