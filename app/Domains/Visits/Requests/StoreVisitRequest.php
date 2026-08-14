<?php

namespace App\Domains\Visits\Requests;

use App\Domains\Sales\SaleFields\SaleFieldsPolicyResolver;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Support\FollowUpSchedule;
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
        $merged = $this->nullEmptySaleFields();

        if ($this->filled('follow_up_at')) {
            $normalized = FollowUpSchedule::normalize($this->input('follow_up_at'));
            $merged['follow_up_at'] = $normalized?->format('Y-m-d H:i:s');
        }

        $birth = trim((string) ($merged['customer_birth_date'] ?? $this->input('customer_birth_date', '')));
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $birth, $m)) {
            $merged['customer_birth_date'] = $m[3].'-'.$m[2].'-'.$m[1];
        }

        $this->merge($merged);
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
            'customer_birth_date', 'due_day', 'install_street', 'install_number',
            'install_neighborhood', 'install_reference', 'install_city',
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
        $saleRules = app(SaleFieldsPolicyResolver::class)->validationRulesForRequest(
            $isSale,
            $companyId,
            $this->requiresDueDayOnSale(),
        );

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
            // Sprint 8.2.16 — Retorno exige data utilizável → FollowUp → Agenda.
            'follow_up_at' => [
                Rule::requiredIf(fn () => $this->input('status') === VisitStatus::RETURN_LATER->value),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ], $saleRules);
    }

    public function messages(): array
    {
        return array_merge([
            'follow_up_at.required' => 'Informe a data do retorno.',
        ], app(SaleFieldsPolicyResolver::class)->validationMessages());
    }

    /**
     * Confirmar venda no mapa/agenda web exige vencimento. Mobile legado permanece opcional.
     */
    protected function requiresDueDayOnSale(): bool
    {
        return true;
    }
}
