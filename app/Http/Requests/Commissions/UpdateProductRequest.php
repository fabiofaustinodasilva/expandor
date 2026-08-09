<?php

namespace App\Http\Requests\Commissions;

use App\Domains\Sales\Products\Models\Product;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $this->user()?->can('update', $product) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'benefits' => ['nullable', 'string', 'max:4000'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'image' => app(MediaUploadService::class)->rules(MediaPurpose::ProductImage),
            'remove_image' => ['sometimes', 'boolean'],
            'price' => ['required', 'numeric', 'min:0'],
            'commission_type' => ['required', Rule::in(\App\Domains\Commissions\Enums\ProductCommissionType::values())],
            'commission_amount' => ['required_if:commission_type,fixed', 'nullable', 'numeric', 'min:0'],
            'commission_percentage' => ['required_if:commission_type,percentage', 'nullable', 'numeric', 'between:0,100'],
            'stock_control' => ['sometimes', 'boolean'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'status' => ['required', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
        ];
    }

    public function messages(): array
    {
        return array_merge(
            [
                'name.required' => 'Informe o nome do produto.',
                'price.required' => 'Informe o preço.',
                'video_url.url' => 'Informe uma URL de vídeo válida.',
            ],
            app(MediaUploadService::class)->validationMessages('image', 'imagem do produto'),
        );
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        if ($key !== null) {
            return $data;
        }

        $data['benefits'] = \App\Domains\Sales\Products\Services\ProductCatalogService::parseBenefitsInput($data['benefits'] ?? null);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['video_url'] = filled($data['video_url'] ?? null) ? $data['video_url'] : null;
        $data['category'] = filled($data['category'] ?? null) ? trim((string) $data['category']) : null;

        return $data;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'stock_control' => $this->boolean('stock_control'),
            'commission_type' => $this->input('commission_type', 'fixed'),
        ]);

        app(MediaUploadService::class)->assertRequestFilesValid([
            'image' => $this->file('image'),
        ]);
    }
}
