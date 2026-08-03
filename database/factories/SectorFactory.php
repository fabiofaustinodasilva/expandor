<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sector>
 */
class SectorFactory extends Factory
{
    protected $model = Sector::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'city_id' => function (array $attributes) {
                return City::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'name' => fake()->unique()->streetName(),
            'description' => fake()->sentence(),
            'active' => true,
        ];
    }
}
