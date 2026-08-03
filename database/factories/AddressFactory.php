<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Territory\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'city_id' => function (array $attributes) {
                return City::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'sector_id' => null,
            'street' => fake()->streetName(),
            'number' => (string) fake()->buildingNumber(),
            'complement' => null,
            'neighborhood' => fake()->citySuffix(),
            'zipcode' => fake()->numerify('#####-###'),
            'latitude' => fake()->latitude(-33, 5),
            'longitude' => fake()->longitude(-73, -34),
        ];
    }
}
