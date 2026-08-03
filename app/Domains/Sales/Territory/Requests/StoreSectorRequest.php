<?php

namespace App\Domains\Sales\Territory\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();

        return [
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sectors', 'name')->where(fn ($q) => $q
                    ->where('company_id', $companyId)
                    ->where('city_id', $this->input('city_id'))),
            ],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
