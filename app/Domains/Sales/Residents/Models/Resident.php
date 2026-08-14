<?php

namespace App\Domains\Sales\Residents\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Enums\ResidentStatus;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\ResidentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resident extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'property_id',
        'name',
        'phone',
        'whatsapp',
        'email',
        'document',
        'birth_date',
        'is_primary_contact',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary_contact' => 'boolean',
            'birth_date' => 'date',
            'status' => ResidentStatus::class,
        ];
    }

    protected static function newFactory(): ResidentFactory
    {
        return ResidentFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ResidentHistory::class);
    }
}
