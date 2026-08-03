<?php

namespace App\Domains\SalesApp\Requests;

use App\Domains\Visits\Enums\VisitStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(VisitStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'schedule_follow_up' => ['sometimes', 'boolean'],
            'follow_up_at' => ['nullable', 'required_if:schedule_follow_up,1', 'date', 'after:now'],
            'follow_up_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'schedule_follow_up' => $this->boolean('schedule_follow_up'),
        ]);
    }
}
