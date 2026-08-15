<?php

namespace App\Domains\Sales\Products\Models;

use App\Domains\Commissions\Enums\ProductCommissionType;
use App\Domains\Company\Models\Company;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleItem;
use App\Domains\Visits\Models\Visit;
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
        'category',
        'description',
        'benefits',
        'image',
        'image_thumb',
        'video_url',
        'price',
        'commission_type',
        'commission_amount',
        'commission_percentage',
        'stock_control',
        'stock_quantity',
        'minimum_stock',
        'status',
        'sort_order',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'commission_type' => ProductCommissionType::class,
            'commission_amount' => 'decimal:2',
            'commission_percentage' => 'decimal:4',
            'stock_control' => 'boolean',
            'stock_quantity' => 'integer',
            'minimum_stock' => 'integer',
            'sort_order' => 'integer',
            'is_demo' => 'boolean',
            'benefits' => 'array',
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

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function commissionType(): ProductCommissionType
    {
        $type = $this->commission_type;

        return $type instanceof ProductCommissionType
            ? $type
            : ProductCommissionType::tryFrom((string) $type) ?? ProductCommissionType::Fixed;
    }

    public function isFixedCommission(): bool
    {
        return $this->commissionType() === ProductCommissionType::Fixed;
    }

    public function isPercentageCommission(): bool
    {
        return $this->commissionType() === ProductCommissionType::Percentage;
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
        $counts = [
            'sales_commissions_count',
            'sale_items_count',
            'sales_count',
            'visits_count',
        ];
        $loaded = false;
        $total = 0;
        foreach ($counts as $attr) {
            if (array_key_exists($attr, $this->attributes)) {
                $loaded = true;
                $total += (int) $this->attributes[$attr];
            }
        }
        if ($loaded) {
            return $total > 0;
        }

        return $this->salesCommissions()->exists()
            || $this->saleItems()->exists()
            || $this->sales()->exists()
            || $this->visits()->exists();
    }

    public function imageUrl(): ?string
    {
        return app(\App\Domains\Media\Services\MediaUploadService::class)->url($this->image_thumb ?: $this->image);
    }

    public function imageOriginalUrl(): ?string
    {
        return app(\App\Domains\Media\Services\MediaUploadService::class)->url($this->image);
    }

    /**
     * High-res public URL from the original column (web present source), APP_URL origin.
     * Does not fall back to the thumbnail.
     */
    public function imageOriginalPublicUrl(): ?string
    {
        $media = app(\App\Domains\Media\Services\MediaUploadService::class);

        return $media->toAbsolutePublicUrl($this->image ?: $this->imageOriginalUrl());
    }

    public function imageThumbPublicUrl(): ?string
    {
        $media = app(\App\Domains\Media\Services\MediaUploadService::class);

        return $media->toAbsolutePublicUrl($this->image_thumb);
    }

    /**
     * Compatibility field: original when present, otherwise thumb (web present order).
     */
    public function imagePresentPublicUrl(): ?string
    {
        return $this->imageOriginalPublicUrl() ?: $this->imageThumbPublicUrl();
    }

    /**
     * @return list<string>
     */
    public function benefitList(): array
    {
        $benefits = $this->benefits;
        if (! is_array($benefits)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($item) => trim((string) $item),
            $benefits
        ), static fn (string $item) => $item !== ''));
    }

    public function categoryLabel(): string
    {
        $category = trim((string) ($this->category ?? ''));

        return $category !== '' ? $category : 'Produto';
    }

    public function embeddableVideoUrl(): ?string
    {
        $url = trim((string) ($this->video_url ?? ''));
        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return $url;
    }
}
