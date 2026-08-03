<?php

namespace App\Domains\Communication\Requests;

use App\Domains\Communication\Enums\MessageDirection;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();

        return [
            'resident_id' => [
                'required',
                'integer',
                Rule::exists('residents', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'message' => ['required', 'string', 'max:5000'],
            'direction' => ['nullable', Rule::enum(MessageDirection::class)],
            'queue_send' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'queue_send' => $this->boolean('queue_send'),
        ]);
    }
}
