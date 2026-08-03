<?php

namespace App\Domains\Payments\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Payments\Enums\BillingCycle;
use App\Domains\Payments\Enums\CheckoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckoutSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'company_id',
        'customer_id',
        'status',
        'gateway',
        'gateway_session_id',
        'checkout_url',
        'buyer_name',
        'buyer_email',
        'buyer_document',
        'buyer_phone',
        'company_name',
        'amount',
        'currency',
        'billing_cycle',
        'expires_at',
        'paid_at',
        'provisioned_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => CheckoutStatus::class,
            'billing_cycle' => BillingCycle::class,
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'provisioned_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\CheckoutSessionFactory::new();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
