<?php

namespace App\Domains\Commissions\Models;

use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\SalesCommissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesCommission extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'visit_id',
        'sale_item_id',
        'product_id',
        'product_name',
        'commission_amount',
        'quantity',
        'status',
        'earned_at',
        'approved_at',
        'paid_at',
        'approved_by',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'commission_amount' => 'decimal:2',
            'quantity' => 'integer',
            'status' => SalesCommissionStatus::class,
            'earned_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SalesCommissionFactory
    {
        return SalesCommissionFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Sales\Models\SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
