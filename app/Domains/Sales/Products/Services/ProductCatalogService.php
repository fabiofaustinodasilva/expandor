<?php

namespace App\Domains\Sales\Products\Services;

use App\Domains\Company\Models\User;
use App\Domains\Media\Enums\MediaCategory;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductCatalogService
{
    public function __construct(
        protected StockService $stock,
        protected SecurityService $security,
        protected MediaUploadService $media,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($data, $actor, $image) {
            $initialStock = (int) ($data['stock_quantity'] ?? 0);
            $stockControl = (bool) ($data['stock_control'] ?? false);

            $imagePath = null;
            $thumbPath = null;
            if ($image instanceof UploadedFile) {
                $result = $this->media->store(
                    $image,
                    (int) $actor->company_id,
                    MediaCategory::Products,
                    MediaPurpose::ProductImage,
                    field: 'image',
                );
                $imagePath = $result->path;
                $thumbPath = $result->thumbPath;
            }

            $product = Product::query()->create([
                'name' => $data['name'],
                'category' => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'image' => $imagePath,
                'image_thumb' => $thumbPath,
                'video_url' => $data['video_url'] ?? null,
                'price' => $data['price'] ?? 0,
                'commission_amount' => $data['commission_amount'] ?? 0,
                'stock_control' => $stockControl,
                'stock_quantity' => 0,
                'minimum_stock' => $data['minimum_stock'] ?? 0,
                'status' => $data['status'] ?? Product::STATUS_ACTIVE,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_demo' => false,
            ]);

            if ($stockControl && $initialStock > 0) {
                $this->stock->entry($product, $initialStock, $actor, 'Estoque inicial do cadastro');
            }

            $this->security->recordAudit(
                action: 'product.created',
                user: $actor,
                auditable: $product,
                newValues: $product->fresh()->toArray(),
            );

            return $product->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data, User $actor, ?UploadedFile $image = null, bool $removeImage = false): Product
    {
        $old = $product->only([
            'name', 'category', 'description', 'benefits', 'image', 'image_thumb', 'video_url',
            'price', 'commission_amount', 'stock_control', 'minimum_stock', 'status', 'sort_order',
        ]);

        $payload = [
            'name' => $data['name'] ?? $product->name,
            'category' => array_key_exists('category', $data) ? $data['category'] : $product->category,
            'description' => array_key_exists('description', $data) ? $data['description'] : $product->description,
            'benefits' => array_key_exists('benefits', $data) ? $data['benefits'] : $product->benefits,
            'video_url' => array_key_exists('video_url', $data) ? $data['video_url'] : $product->video_url,
            'price' => $data['price'] ?? $product->price,
            'commission_amount' => $data['commission_amount'] ?? $product->commission_amount,
            'stock_control' => array_key_exists('stock_control', $data)
                ? (bool) $data['stock_control']
                : $product->stock_control,
            'minimum_stock' => $data['minimum_stock'] ?? $product->minimum_stock,
            'status' => $data['status'] ?? $product->status,
            'sort_order' => array_key_exists('sort_order', $data)
                ? (int) $data['sort_order']
                : $product->sort_order,
        ];

        if ($image instanceof UploadedFile) {
            $result = $this->media->store(
                $image,
                (int) $product->company_id,
                MediaCategory::Products,
                MediaPurpose::ProductImage,
                $product->image,
                $product->image_thumb,
                'image',
            );
            $payload['image'] = $result->path;
            $payload['image_thumb'] = $result->thumbPath;
        } elseif ($removeImage) {
            $this->media->delete($product->image, $product->image_thumb);
            $payload['image'] = null;
            $payload['image_thumb'] = null;
        }

        $product->update($payload);

        $this->security->recordAudit(
            action: 'product.updated',
            user: $actor,
            auditable: $product,
            oldValues: $old,
            newValues: $product->fresh()->only(array_keys($old)),
        );

        return $product->fresh();
    }

    public function deleteOrFail(Product $product, User $actor): void
    {
        if ($product->hasLinkedSales()) {
            throw ValidationException::withMessages([
                'product' => 'Produto com vendas vinculadas não pode ser excluído. Inative-o.',
            ]);
        }

        $this->security->recordAudit(
            action: 'product.deleted',
            user: $actor,
            auditable: $product,
            oldValues: $product->toArray(),
        );

        $this->media->delete($product->image, $product->image_thumb);
        $product->delete();
    }

    /**
     * @return list<array{id: int, name: string, price: float|string, commission_amount: float|string, stock_control: bool, stock_quantity: int}>
     */
    public function sellableOptions(): array
    {
        return Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'price',
                'commission_amount',
                'stock_control',
                'stock_quantity',
                'status',
            ])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'commission_amount' => $p->commission_amount,
                'stock_control' => $p->stock_control,
                'stock_quantity' => $p->stock_quantity,
                'available' => $p->isSellable(),
            ])
            ->values()
            ->all();
    }

    /**
     * Active catalog for field sellers (tenant-scoped via BelongsToTenant).
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public function activeCatalogForSeller(?string $search = null)
    {
        $query = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name');

        $term = trim((string) $search);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        return $query->get();
    }

    /**
     * @return list<string>|null
     */
    public static function parseBenefitsInput(null|string|array $raw): ?array
    {
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $text = trim((string) $raw);
            if ($text === '') {
                return null;
            }
            $items = preg_split('/\r\n|\r|\n/', $text) ?: [];
        }

        $clean = array_values(array_filter(array_map(
            static fn ($item) => trim((string) $item),
            $items
        ), static fn (string $item) => $item !== ''));

        return $clean === [] ? null : $clean;
    }
}
