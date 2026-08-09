<?php

namespace App\Domains\Sales\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Sales\Products\Models\Product;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SaleItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'sale_id',
        'product_id',
        'product_name',
        'unit_price',
        'quantity',
        'line_total',
        'commission_amount',
        'commission_type',
        'commission_rate',
        'commission_base',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'commission_rate' => 'decimal:4',
            'commission_base' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salesCommission(): HasOne
    {
        return $this->hasOne(SalesCommission::class);
    }
}
