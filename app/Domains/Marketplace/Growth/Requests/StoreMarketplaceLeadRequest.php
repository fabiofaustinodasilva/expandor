<?php

namespace App\Domains\Marketplace\Growth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketplaceLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'segment' => ['nullable', 'string', 'max:120'],
            'employees' => ['nullable', 'string', 'max:40'],
            'source' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
