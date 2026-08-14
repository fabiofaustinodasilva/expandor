<?php

namespace App\Domains\Mobile\Requests\Concerns;

use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Domains\Visits\Enums\VisitStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait RequiresDueDayOnCompleteSale
{
    protected function requiresDueDayOnSale(): bool
    {
        return $this->boolean('complete_sale');
    }

    /**
     * @return array<string, mixed>
     */
    protected function completeSaleExtraRules(): array
    {
        if (! $this->boolean('complete_sale')
            || $this->input('status') !== VisitStatus::INSTALLATION_REQUESTED->value) {
            return [
                'complete_sale' => ['sometimes', 'boolean'],
            ];
        }

        return [
            'complete_sale' => ['sometimes', 'boolean'],
            'customer_birth_date' => ['required', 'date'],
            'install_street' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function completeSaleExtraMessages(): array
    {
        return [
            'customer_birth_date.required' => 'Informe a data de nascimento.',
            'install_street.required' => 'Informe a rua / avenida.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $first = collect($errors)->flatten()->first() ?: 'Verifique os dados informados.';

        throw new HttpResponseException(
            MobileAuthResponse::error((string) $first, 'validation_error', 422, $errors)
        );
    }
}
