<?php

namespace App\Domains\Sales\Territory\Requests;

use App\Domains\Sales\Territory\Models\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;
        /** @var City $city */
        $city = $this->route('city');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cities', 'name')
                    ->where(fn ($q) => $q
                        ->where('company_id', $companyId)
                        ->where('state', strtoupper((string) $this->input('state'))))
                    ->ignore($city->id),
            ],
            'state' => ['required', 'string', 'size:2'],
            'ibge_code' => ['nullable', 'string', 'max:10'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
