<?php

namespace App\Domains\Platform\Requests;

use App\Domains\Company\Models\Plan;
use App\Domains\Payments\Services\CommercialContractService;
use App\Domains\Security\Services\RegistrationIntegrityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlatformCompanyRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:32'],
            'company_email' => ['required', 'email', 'max:255'],
            'company_phone' => ['required', 'string', 'max:30'],
            'company_city' => ['required', 'string', 'max:120'],
            'company_uf' => ['required', 'string', 'size:2'],
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(fn ($q) => $q->where('status', Plan::STATUS_ACTIVE)),
            ],
            'contract_started_at' => ['required', 'date'],
            'billing_day' => ['required', 'integer', Rule::in(CommercialContractService::BILLING_DAYS)],
            'fidelity_mode' => ['required', Rule::in([
                CommercialContractService::FIDELITY_NONE,
                CommercialContractService::FIDELITY_3,
                CommercialContractService::FIDELITY_6,
                CommercialContractService::FIDELITY_12,
                CommercialContractService::FIDELITY_CUSTOM,
            ])],
            'fidelity_custom_months' => [
                'nullable',
                'integer',
                'min:1',
                'max:60',
                Rule::requiredIf(fn () => $this->input('fidelity_mode') === CommercialContractService::FIDELITY_CUSTOM),
            ],
            'has_commercial_exception' => ['sometimes', 'boolean'],
            'negotiated_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                Rule::requiredIf(fn () => $this->boolean('has_commercial_exception')),
            ],
            'first_due_at' => [
                'nullable',
                'date',
                'after_or_equal:contract_started_at',
                Rule::requiredIf(fn () => $this->boolean('has_commercial_exception') && filled($this->input('first_due_at_override'))),
            ],
            'first_due_at_override' => ['sometimes', 'boolean'],
            'commercial_exception_reason' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn () => $this->boolean('has_commercial_exception')),
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_phone' => ['required', 'string', 'max:30'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'nome fantasia',
            'legal_name' => 'razão social',
            'document' => 'CPF/CNPJ',
            'company_email' => 'e-mail da empresa',
            'company_phone' => 'telefone / WhatsApp',
            'company_city' => 'cidade',
            'company_uf' => 'UF',
            'plan_id' => 'plano',
            'contract_started_at' => 'início do contrato',
            'billing_day' => 'dia de vencimento',
            'fidelity_mode' => 'fidelidade',
            'fidelity_custom_months' => 'meses de fidelidade',
            'negotiated_amount' => 'mensalidade negociada',
            'first_due_at' => 'primeiro vencimento',
            'commercial_exception_reason' => 'motivo da exceção',
            'admin_name' => 'nome do administrador',
            'admin_email' => 'e-mail do administrador',
            'admin_phone' => 'telefone do administrador',
            'admin_password' => 'senha do administrador',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            try {
                app(RegistrationIntegrityService::class)->assertRegistrationIdentityAvailable(
                    (string) $this->input('admin_email'),
                    $this->input('document'),
                    'admin_email',
                    'document',
                );
            } catch (\Illuminate\Validation\ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }

            if ($this->boolean('has_commercial_exception') && $this->boolean('first_due_at_override') && ! $this->filled('first_due_at')) {
                $validator->errors()->add('first_due_at', 'Informe o primeiro vencimento personalizado.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'admin_email' => strtolower(trim((string) $this->input('admin_email'))),
            'company_email' => strtolower(trim((string) $this->input('company_email'))),
            'company_uf' => strtoupper(trim((string) $this->input('company_uf'))),
            'has_commercial_exception' => $this->boolean('has_commercial_exception'),
            'first_due_at_override' => $this->boolean('first_due_at_override'),
            'fidelity_mode' => $this->input('fidelity_mode', CommercialContractService::FIDELITY_6),
        ]);
    }
}
