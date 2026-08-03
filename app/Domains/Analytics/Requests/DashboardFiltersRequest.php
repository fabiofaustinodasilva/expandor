<?php

namespace App\Domains\Analytics\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();

        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'city_id' => [
                'nullable',
                'integer',
                Rule::exists('cities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'sector_id' => [
                'nullable',
                'integer',
                Rule::exists('sectors', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ];
    }
}
