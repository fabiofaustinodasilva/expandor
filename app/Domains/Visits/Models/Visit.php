<?php

namespace App\Domains\Visits\Models;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Enums\VisitStatus;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visit extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'campaign_id',
        'property_id',
        'user_id',
        'status',
        'notes',
        'plan',
        'product_id',
        'latitude',
        'longitude',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VisitStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'visited_at' => 'datetime',
        ];
    }

    protected static function newFactory(): VisitFactory
    {
        return VisitFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salesCommission(): HasOne
    {
        return $this->hasOne(SalesCommission::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(\App\Domains\Sales\Models\Sale::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }
}
