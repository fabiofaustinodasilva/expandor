<?php

namespace App\Domains\CRM\Requests;

use App\Domains\CRM\Enums\LeadSource;
use App\Domains\CRM\Enums\LeadStatus;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
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
        $companyId = app(TenantContext::class)->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', Rule::in(array_keys(LeadSource::options()))],
            'status' => ['nullable', Rule::in(array_keys(LeadStatus::options()))],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'campaign_id' => [
                'nullable',
                'integer',
                Rule::exists('campaigns', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'property_id' => [
                'nullable',
                'integer',
                Rule::exists('properties', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'resident_id' => [
                'nullable',
                'integer',
                Rule::exists('residents', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
