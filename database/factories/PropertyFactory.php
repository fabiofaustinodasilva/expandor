<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Enums\PropertyType;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'address_id' => function (array $attributes) {
                return Address::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'type' => PropertyType::HOUSE,
            'status' => PropertyStatus::NEW,
            'latitude' => fake()->latitude(-33, 5),
            'longitude' => fake()->longitude(-73, -34),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
