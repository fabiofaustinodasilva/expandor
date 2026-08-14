<?php

namespace App\Domains\Sales\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dossiê comercial 1:1 com a visita de venda (installation_requested).
 * Itens em sale_items; total calculado automaticamente.
 */
class Sale extends Model
{
    use BelongsToTenant;

    protected $table = 'sales';

    protected $fillable = [
        'company_id',
        'visit_id',
        'resident_id',
        'product_id',
        'negotiated_amount',
        'due_day',
        'notes',
        'attributes',
    ];

    protected function casts(): array
    {
        return [
            'negotiated_amount' => 'decimal:2',
            'due_day' => 'integer',
            'attributes' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
