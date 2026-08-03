<?php

namespace App\Domains\Sales\Territory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cities', 'name')->where(fn ($q) => $q
                    ->where('company_id', $companyId)
                    ->where('state', strtoupper((string) $this->input('state')))),
            ],
            'state' => ['required', 'string', 'size:2'],
            'ibge_code' => ['nullable', 'string', 'max:10'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
