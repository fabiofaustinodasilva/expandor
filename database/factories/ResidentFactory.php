<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Enums\ResidentStatus;
use App\Domains\Sales\Residents\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resident>
 */
class ResidentFactory extends Factory
{
    protected $model = Resident::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'property_id' => function (array $attributes) {
                return Property::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'name' => fake()->name(),
            'phone' => fake()->numerify('(##) #####-####'),
            'email' => fake()->safeEmail(),
            'document' => fake()->optional()->numerify('###.###.###-##'),
            'is_primary_contact' => false,
            'status' => ResidentStatus::ACTIVE,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary_contact' => true]);
    }
}
