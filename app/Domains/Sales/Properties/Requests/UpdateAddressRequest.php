<?php

namespace App\Domains\Sales\Properties\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddressRequest extends FormRequest
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
            'sector_id' => [
                'nullable',
                Rule::exists('sectors', 'id')->where(fn ($q) => $q
                    ->where('company_id', $companyId)
                    ->where('city_id', $this->input('city_id'))),
            ],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:30'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'zipcode' => ['nullable', 'string', 'max:12'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
