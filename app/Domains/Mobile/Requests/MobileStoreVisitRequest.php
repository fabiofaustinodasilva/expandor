<?php

namespace App\Domains\Mobile\Requests;

use App\Domains\Sales\SaleFields\SaleFieldsPolicyResolver;
use App\Domains\Visits\Enums\VisitStatus;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MobileStoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Campos de venda são aditivos/opcionais nesta sprint (tela Finalizar venda no app = sprint futura).
     * Quando status = installation_requested e a empresa exigir campos, a validação dinâmica aplica.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();
        $isSale = $this->input('status') === VisitStatus::INSTALLATION_REQUESTED->value;
        $saleRules = app(SaleFieldsPolicyResolver::class)->validationRulesForRequest($isSale, $companyId, false);

        // Mobile legado: se não enviar campos de venda, não quebrar prospecção.
        // Venda completa no app virá depois — por enquanto, sale rules só se payload indicar venda.
        // Para compatibilidade: se installation_requested sem campos de cliente, relaxar required
        // apenas quando nenhum campo de venda foi enviado (app antigo).
        if ($isSale && ! $this->hasAnySalePayload()) {
            $saleRules = [];
        }

        return array_merge([
            'campaign_id' => ['required', 'integer', 'exists:campaigns,id'],
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'status' => ['required', Rule::enum(VisitStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'client_uuid' => ['nullable', 'uuid'],
            'visited_at' => ['nullable', 'date'],
            'schedule_follow_up' => ['sometimes', 'boolean'],
            'follow_up_at' => ['nullable', 'required_if:schedule_follow_up,1', 'date'],
            'follow_up_notes' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_whatsapp' => ['nullable', 'string', 'max:30'],
            'customer_document' => ['nullable', 'string', 'max:32'],
            'customer_rg' => ['nullable', 'string', 'max:32'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'negotiated_amount' => ['nullable', 'numeric', 'min:0'],
            'sale_notes' => ['nullable', 'string', 'max:5000'],
        ], $saleRules);
    }

    public function messages(): array
    {
        return app(SaleFieldsPolicyResolver::class)->validationMessages();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('schedule_follow_up')) {
            $this->merge([
                'schedule_follow_up' => $this->boolean('schedule_follow_up'),
            ]);
        }
    }

    protected function hasAnySalePayload(): bool
    {
        foreach ([
            'customer_name', 'customer_phone', 'customer_whatsapp', 'customer_document',
            'customer_rg', 'customer_email', 'product_id', 'negotiated_amount', 'sale_notes',
        ] as $key) {
            if ($this->filled($key)) {
                return true;
            }
        }

        return false;
    }
}
