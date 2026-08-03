<?php

namespace App\Domains\AI\Requests;

use App\Domains\AI\Enums\AIContextType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAIConversationRequest extends FormRequest
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
            'question' => ['required', 'string', 'min:3', 'max:4000'],
            'context_type' => ['required', 'string', Rule::enum(AIContextType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'question' => 'pergunta',
            'context_type' => 'contexto',
        ];
    }
}
