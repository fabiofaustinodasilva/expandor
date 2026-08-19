<?php

namespace App\Domains\Marketplace\Growth\Requests;

use App\Domains\Marketplace\Growth\Support\BrazilianPhone;
use App\Domains\Marketplace\Growth\Support\BrazilianStates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'company_name' => ['required', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:40'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2', Rule::in(BrazilianStates::codes())],
            'sellers_count' => ['required', 'integer', 'min:1', 'max:999'],
            'customers_count' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'segment' => ['nullable', 'string', 'max:120'],
            'employees' => ['nullable', 'string', 'max:40'],
            'source' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Informe o nome do provedor.',
            'phone.required' => 'Informe o WhatsApp.',
            'city.required' => 'Informe a cidade.',
            'state.required' => 'Informe o estado.',
            'sellers_count.required' => 'Informe a quantidade de vendedores externos.',
            'website.max' => 'Não foi possível enviar o formulário.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $phone = (string) $this->input('phone', '');
            if ($phone !== '' && ! BrazilianPhone::isValid($phone)) {
                $validator->errors()->add('phone', 'Informe um WhatsApp brasileiro válido.');
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        return route('marketplace.home').'#demo';
    }
}
