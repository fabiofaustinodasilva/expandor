<?php

namespace App\Domains\Visits\Requests;

use App\Domains\Sales\SaleFields\SaleFieldsPolicyResolver;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Support\FollowUpSchedule;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Contrato do primeiro atendimento (web seller; reutilizável no mobile depois).
 */
class StoreFirstApproachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('follow_up_at')) {
            $normalized = FollowUpSchedule::normalize($this->input('follow_up_at'));
            $this->merge([
                'follow_up_at' => $normalized?->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();
        $isSale = $this->input('status') === VisitStatus::INSTALLATION_REQUESTED->value;
        $saleRules = app(SaleFieldsPolicyResolver::class)->validationRulesForRequest($isSale, $companyId);

        return array_merge([
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'sector_id' => [
                'nullable',
                'integer',
                Rule::exists('sectors', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:30'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'status' => [
                'required',
                Rule::in([
                    VisitStatus::INTERESTED->value,
                    VisitStatus::INSTALLATION_REQUESTED->value,
                    VisitStatus::RETURN_LATER->value,
                    VisitStatus::NO_INTEREST->value,
                    VisitStatus::NOT_HOME->value,
                ]),
            ],
            'campaign_id' => ['nullable', 'integer'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'plan' => ['nullable', 'string', 'max:120'],
            'gps_accuracy' => ['nullable', 'numeric', 'min:0'],
            // Sprint 8.2.16 — Retorno sem data não pode “esquecer” a Agenda.
            'follow_up_at' => [
                Rule::requiredIf(fn () => $this->input('status') === VisitStatus::RETURN_LATER->value),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'visited_at' => ['nullable', 'date'],
        ], $saleRules);
    }

    public function messages(): array
    {
        return array_merge([
            'status.required' => 'Escolha o resultado do atendimento.',
            'street.required' => 'Informe a rua ou use Local GPS.',
            'follow_up_at.required' => 'Informe a data do retorno.',
        ], app(SaleFieldsPolicyResolver::class)->validationMessages());
    }
}
