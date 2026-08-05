<?php

namespace App\Domains\Platform\Requests;

use App\Domains\Company\Models\Plan;
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
            'legal_name' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:32'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(fn ($q) => $q->where('status', Plan::STATUS_ACTIVE)),
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_phone' => ['nullable', 'string', 'max:30'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'nome da empresa',
            'plan_id' => 'plano',
            'admin_name' => 'nome do administrador',
            'admin_email' => 'e-mail do administrador',
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
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('admin_email')) {
            $this->merge([
                'admin_email' => strtolower(trim((string) $this->input('admin_email'))),
            ]);
        }
    }
}
