<?php

namespace Database\Factories;

use App\Domains\Communication\Enums\MessageDirection;
use App\Domains\Communication\Enums\MessageStatus;
use App\Domains\Communication\Models\Message;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Residents\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'resident_id' => function (array $attributes) {
                return Resident::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'user_id' => function (array $attributes) {
                return User::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'direction' => MessageDirection::OUTBOUND,
            'message' => fake()->sentence(),
            'status' => MessageStatus::PENDING,
            'sent_at' => null,
        ];
    }
}
