<?php

namespace App\Domains\CRM\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Enums\CommissionStatus;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\CommissionEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionEntry extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'opportunity_id',
        'user_id',
        'commission_rule_id',
        'base_amount',
        'percent',
        'commission_amount',
        'status',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'percent' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'status' => CommissionStatus::class,
            'calculated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CommissionEntryFactory
    {
        return CommissionEntryFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }
}
