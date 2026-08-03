<?php

namespace App\Domains\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyAdminContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.manageCompanies') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'administrador',
            'email' => 'e-mail',
            'phone' => 'telefone',
            'whatsapp' => 'WhatsApp',
        ];
    }
}
