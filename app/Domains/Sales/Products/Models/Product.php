<?php

namespace App\Domains\Sales\Products\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Commissions\Models\SalesCommission;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'image',
        'image_thumb',
        'price',
        'commission_amount',
        'stock_control',
        'stock_quantity',
        'minimum_stock',
        'status',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'stock_control' => 'boolean',
            'stock_quantity' => 'integer',
            'minimum_stock' => 'integer',
            'is_demo' => 'boolean',
        ];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function salesCommissions(): HasMany
    {
        return $this->hasMany(SalesCommission::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSellable(int $quantity = 1): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if (! $this->stock_control) {
            return true;
        }

        return $this->stock_quantity >= $quantity;
    }

    public function isBelowMinimum(): bool
    {
        return $this->stock_control && $this->stock_quantity <= $this->minimum_stock;
    }

    public function hasLinkedSales(): bool
    {
        return $this->salesCommissions()->exists();
    }

    public function imageUrl(): ?string
    {
        return app(\App\Domains\Media\Services\MediaUploadService::class)->url($this->image_thumb ?: $this->image);
    }

    public function imageOriginalUrl(): ?string
    {
        return app(\App\Domains\Media\Services\MediaUploadService::class)->url($this->image);
    }
}
