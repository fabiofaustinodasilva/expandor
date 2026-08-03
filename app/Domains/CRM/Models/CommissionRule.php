<?php

namespace App\Domains\CRM\Models;

use App\Domains\Company\Models\Company;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\CommissionRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRule extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'percent',
        'min_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'percent' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): CommissionRuleFactory
    {
        return CommissionRuleFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class);
    }
}
