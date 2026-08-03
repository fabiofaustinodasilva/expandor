<?php

namespace Database\Factories;

use App\Domains\Payments\Enums\WebhookEventStatus;
use App\Domains\Payments\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition(): array
    {
        return [
            'gateway' => 'fake',
            'event_id' => 'evt_'.fake()->unique()->numerify('######'),
            'event_type' => 'PAYMENT_CONFIRMED',
            'payload' => ['ok' => true],
            'status' => WebhookEventStatus::Received,
        ];
    }
}
