<?php

namespace Database\Factories;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Company;
use App\Domains\Sales\Territory\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Campanha '.fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'city_id' => function (array $attributes) {
                return City::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'status' => CampaignStatus::DRAFT,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'goal_visits' => fake()->numberBetween(10, 200),
            'created_by' => null,
        ];
    }
}
