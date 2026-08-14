<?php

namespace App\Domains\Visits\Requests;

use App\Domains\Sales\SaleFields\SaleFieldsPolicyResolver;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Support\FollowUpSchedule;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteFollowUpRequest extends FormRequest
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

        $birth = trim((string) $this->input('customer_birth_date', ''));
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $birth, $m)) {
            $this->merge(['customer_birth_date' => $m[3].'-'.$m[2].'-'.$m[1]]);
        }
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
            'status' => ['required', Rule::enum(VisitStatus::class)],
            'notes' => ['nullable', 'string'],
            'plan' => ['nullable', 'string', 'max:120'],
            'follow_up_at' => [
                Rule::requiredIf(fn () => $this->input('status') === VisitStatus::RETURN_LATER->value),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'follow_up_notes' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], $saleRules);
    }

    public function messages(): array
    {
        return array_merge([
            'status.required' => 'Selecione o resultado do retorno.',
            'follow_up_at.required' => 'Informe a data do novo retorno.',
        ], app(SaleFieldsPolicyResolver::class)->validationMessages());
    }

    /**
     * Agenda web exige vencimento na venda. Mobile legado permanece opcional.
     */
    protected function requiresDueDayOnSale(): bool
    {
        return true;
    }
}
