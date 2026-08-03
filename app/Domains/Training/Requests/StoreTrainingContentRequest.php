<?php

namespace App\Domains\Training\Requests;

use App\Domains\Training\Enums\TrainingContentType;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('training_categories', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(TrainingContentType::class)],
            'content' => ['nullable', 'string'],
            'url' => ['nullable', 'url', 'max:2048'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('active')) {
            $this->merge(['active' => $this->boolean('active')]);
        }
    }
}
