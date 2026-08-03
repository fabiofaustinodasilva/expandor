<?php

namespace App\Domains\Sales\Properties\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Enums\PropertyType;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'address_id',
        'type',
        'status',
        'latitude',
        'longitude',
        'notes',
        'created_by',
        'deleted_by',
        'deletion_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'status' => PropertyStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function newFactory(): PropertyFactory
    {
        return PropertyFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PropertyHistory::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Owner used for field-seller delete rules (created_by or first history actor).
     */
    public function ownerUserId(): ?int
    {
        if ($this->created_by) {
            return (int) $this->created_by;
        }

        $historyUserId = $this->histories()
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');

        return $historyUserId !== null ? (int) $historyUserId : null;
    }
}
