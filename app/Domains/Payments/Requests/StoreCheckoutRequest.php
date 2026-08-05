<?php

namespace App\Domains\Payments\Requests;

use App\Domains\Payments\Enums\BillingCycle;
use App\Domains\Payments\Enums\PaymentMethodType;
use App\Domains\Security\Services\RegistrationIntegrityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreCheckoutRequest extends FormRequest
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
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'company_name' => ['required', 'string', 'max:190'],
            'buyer_name' => ['required', 'string', 'max:190'],
            'buyer_email' => ['required', 'email', 'max:190'],
            'buyer_document' => ['required', 'string', 'min:11', 'max:40'],
            'buyer_phone' => ['required', 'string', 'min:8', 'max:40'],
            'billing_cycle' => ['nullable', Rule::enum(BillingCycle::class)],
            'payment_method' => ['nullable', 'string', Rule::in([
                'UNDEFINED', 'PIX', 'BOLETO', 'CREDIT_CARD',
                PaymentMethodType::Pix->value,
                PaymentMethodType::Boleto->value,
                PaymentMethodType::Card->value,
            ])],
            'admin_password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'buyer_email.email' => 'Informe um e-mail válido.',
            'buyer_document.required' => 'Informe o CPF ou CNPJ.',
            'admin_password.required' => 'Informe a senha do administrador.',
            'admin_password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var RegistrationIntegrityService $integrity */
            $integrity = app(RegistrationIntegrityService::class);

            try {
                $integrity->assertCheckoutIdentityAvailable(
                    (string) $this->input('buyer_email'),
                    (string) $this->input('buyer_document'),
                );
            } catch (\Illuminate\Validation\ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('buyer_email')) {
            $this->merge([
                'buyer_email' => strtolower(trim((string) $this->input('buyer_email'))),
            ]);
        }
    }
}
