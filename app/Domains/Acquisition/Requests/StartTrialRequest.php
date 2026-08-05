<?php

namespace App\Domains\Acquisition\Requests;

use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Security\Services\RegistrationIntegrityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StartTrialRequest extends FormRequest
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
            'segment' => ['required', Rule::enum(CompanySegment::class)],
            'document' => ['nullable', 'string', 'max:32'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_whatsapp' => ['required', 'string', 'max:40'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'confirmed', Password::defaults()],
            'with_demo_data' => ['required', 'boolean'],
            'terms_accepted' => ['accepted'],
            'website' => ['nullable', 'max:0'], // honeypot anti-bot
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Informe o nome da empresa.',
            'segment.required' => 'Selecione o segmento.',
            'admin_name.required' => 'Informe o nome do responsável.',
            'admin_whatsapp.required' => 'Informe o WhatsApp/telefone do responsável.',
            'admin_email.required' => 'Informe o e-mail de acesso.',
            'admin_email.email' => 'Informe um e-mail válido.',
            'admin_password.required' => 'Crie uma senha de acesso.',
            'admin_password.confirmed' => 'A confirmação da senha não confere.',
            'with_demo_data.required' => 'Escolha se deseja começar vazio ou com demonstração.',
            'terms_accepted.accepted' => 'Aceite os termos de uso para criar a conta.',
            'website.max' => 'Não foi possível concluir o cadastro.',
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
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'with_demo_data' => $this->boolean('with_demo_data'),
            'terms_accepted' => $this->boolean('terms_accepted'),
            'admin_email' => strtolower(trim((string) $this->input('admin_email'))),
        ]);
    }
}
