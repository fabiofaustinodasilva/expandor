<?php

namespace App\Domains\Payments\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Payments\Enums\PaymentMethodType;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'type',
        'gateway',
        'gateway_payment_method_id',
        'brand',
        'last_four',
        'exp_month',
        'exp_year',
        'is_default',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'is_default' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\PaymentMethodFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
