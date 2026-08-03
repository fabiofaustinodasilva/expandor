<?php

namespace App\Domains\Visits\Requests;

use App\Domains\Sales\SaleFields\SaleFieldsPolicyResolver;
use App\Domains\Visits\Enums\VisitStatus;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->nullEmptySaleFields());
    }

    /**
     * @return array<string, mixed>
     */
    protected function nullEmptySaleFields(): array
    {
        $out = [];
        foreach ([
            'customer_name', 'customer_phone', 'customer_whatsapp', 'customer_document',
            'customer_rg', 'customer_email', 'product_id', 'negotiated_amount', 'sale_notes',
        ] as $key) {
            if ($this->exists($key) && $this->input($key) === '') {
                $out[$key] = null;
            }
        }

        return $out;
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();
        $isSale = $this->input('status') === VisitStatus::INSTALLATION_REQUESTED->value;
        $saleRules = app(SaleFieldsPolicyResolver::class)->validationRulesForRequest($isSale, $companyId);

        return array_merge([
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'status' => ['required', Rule::enum(VisitStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'plan' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'visited_at' => ['nullable', 'date'],
            'follow_up_at' => ['nullable', 'date', 'after_or_equal:today'],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ], $saleRules);
    }

    public function messages(): array
    {
        return app(SaleFieldsPolicyResolver::class)->validationMessages();
    }
}
