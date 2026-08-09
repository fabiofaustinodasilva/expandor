<?php

namespace App\Domains\Campaigns\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('territory_mode') === 'all') {
            $this->merge(['sector_ids' => []]);
        }
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'goal_visits' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'territory_mode' => ['nullable', Rule::in(['all', 'sectors'])],
            'sector_ids' => [
                Rule::requiredIf(fn () => $this->input('territory_mode') === 'sectors'),
                'nullable',
                'array',
            ],
            'sector_ids.*' => [
                'integer',
                Rule::exists('sectors', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('territory_mode') !== 'sectors') {
                return;
            }

            $ids = collect($this->input('sector_ids', []))->filter()->values();
            if ($ids->isEmpty()) {
                $validator->errors()->add(
                    'sector_ids',
                    'Selecione ao menos um setor ou escolha Todos os setores.'
                );
            }
        });
    }
}
