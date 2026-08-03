<?php

namespace App\Domains\Sales\Residents\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Enums\ResidentHistoryEvent;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentHistory extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'resident_id',
        'property_id',
        'event',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event' => ResidentHistoryEvent::class,
            'created_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
