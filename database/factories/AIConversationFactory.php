<?php

namespace Database\Factories;

use App\Domains\AI\Enums\AIContextType;
use App\Domains\AI\Models\AIConversation;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIConversation>
 */
class AIConversationFactory extends Factory
{
    protected $model = AIConversation::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'context_type' => AIContextType::SALES,
            'question' => 'Como melhorar as visitas desta semana?',
            'answer' => 'Sugestão: priorize retornos pendentes nos setores com maior interesse.',
            'provider' => 'fake',
            'tokens_used' => 42,
        ];
    }
}
