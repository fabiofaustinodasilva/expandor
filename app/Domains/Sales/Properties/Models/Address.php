<?php

namespace App\Domains\Sales\Properties\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'city_id',
        'sector_id',
        'street',
        'number',
        'complement',
        'neighborhood',
        'zipcode',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function label(): string
    {
        $parts = array_filter([
            $this->street,
            $this->number,
            $this->neighborhood,
        ]);

        return implode(', ', $parts);
    }
}
