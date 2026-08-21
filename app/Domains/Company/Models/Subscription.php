<?php

namespace App\Domains\Company\Models;

use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_TRIAL = 'trial';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PAST_DUE = 'past_due';

    protected $fillable = [
        'company_id',
        'plan_id',
        'status',
        'starts_at',
        'contract_started_at',
        'minimum_term_months',
        'minimum_term_ends_at',
        'ends_at',
        'trial_ends_at',
        'gateway',
        'gateway_subscription_id',
        'billing_cycle',
        'next_billing_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'contract_started_at' => 'datetime',
            'minimum_term_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'next_billing_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function isInsideMinimumTerm(): bool
    {
        return $this->minimum_term_ends_at !== null && now()->lt($this->minimum_term_ends_at);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
