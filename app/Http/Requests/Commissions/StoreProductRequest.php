<?php

namespace App\Http\Requests\Commissions;

use App\Domains\Sales\Products\Models\Product;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => app(MediaUploadService::class)->rules(MediaPurpose::ProductImage),
            'price' => ['required', 'numeric', 'min:0'],
            'commission_amount' => ['required', 'numeric', 'min:0'],
            'stock_control' => ['sometimes', 'boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
        ];
    }

    public function messages(): array
    {
        return array_merge(
            [
                'name.required' => 'Informe o nome do produto.',
                'price.required' => 'Informe o preço.',
            ],
            app(MediaUploadService::class)->validationMessages('image', 'imagem do produto'),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'stock_control' => $this->boolean('stock_control'),
        ]);

        app(MediaUploadService::class)->assertRequestFilesValid([
            'image' => $this->file('image'),
        ]);
    }
}
