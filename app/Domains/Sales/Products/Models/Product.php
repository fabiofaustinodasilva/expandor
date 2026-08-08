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
        'category',
        'description',
        'benefits',
        'image',
        'image_thumb',
        'video_url',
        'price',
        'commission_amount',
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
            'commission_amount' => 'decimal:2',
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
