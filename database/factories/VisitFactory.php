<?php

namespace Database\Factories;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'campaign_id' => function (array $attributes) {
                return Campaign::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'property_id' => function (array $attributes) {
                return Property::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'user_id' => function (array $attributes) {
                return User::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'status' => VisitStatus::INTERESTED,
            'notes' => fake()->optional()->sentence(),
            'latitude' => fake()->latitude(-33, 5),
            'longitude' => fake()->longitude(-73, -34),
            'visited_at' => now(),
        ];
    }
}
