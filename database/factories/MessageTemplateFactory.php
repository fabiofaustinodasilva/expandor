<?php

namespace Database\Factories;

use App\Domains\Communication\Enums\MessageTemplateEvent;
use App\Domains\Communication\Models\MessageTemplate;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageTemplate>
 */
class MessageTemplateFactory extends Factory
{
    protected $model = MessageTemplate::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Template '.fake()->unique()->words(2, true),
            'event' => MessageTemplateEvent::GENERIC,
            'content' => 'Olá {{nome}}, seguimos à disposição.',
            'active' => true,
        ];
    }
}
