<?php

namespace App\Domains\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeCompanyAdministratorRequest extends FormRequest
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'novo administrador',
        ];
    }
}
