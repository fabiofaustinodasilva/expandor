<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    protected $model = FollowUp::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'visit_id' => function (array $attributes) {
                return Visit::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'user_id' => function (array $attributes) {
                return User::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'scheduled_at' => now()->addDay(),
            'status' => FollowUpStatus::PENDING,
            'notes' => fake()->optional()->sentence(),
            'completed_at' => null,
        ];
    }
}
