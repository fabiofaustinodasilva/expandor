<?php

namespace App\Domains\Sales\Territory\Requests;

use App\Domains\Sales\Territory\Models\Sector;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();
        /** @var Sector $sector */
        $sector = $this->route('sector');

        return [
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sectors', 'name')
                    ->where(fn ($q) => $q
                        ->where('company_id', $companyId)
                        ->where('city_id', $this->input('city_id')))
                    ->ignore($sector->id),
            ],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
