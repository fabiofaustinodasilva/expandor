<?php

namespace App\Domains\Visits\Requests;

use App\Domains\Visits\Support\FollowUpSchedule;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('scheduled_date') || $this->filled('scheduled_at')) {
            $normalized = $this->filled('scheduled_date')
                ? FollowUpSchedule::fromDateAndTime(
                    $this->input('scheduled_date'),
                    $this->input('scheduled_time')
                )
                : FollowUpSchedule::normalize($this->input('scheduled_at'));

            $this->merge([
                'scheduled_at' => $normalized?->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();

        return [
            'scheduled_date' => ['nullable', 'date', 'required_without:scheduled_at'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.required_without' => 'Informe a data do retorno.',
            'scheduled_at.required' => 'Informe a data do retorno.',
        ];
    }
}
