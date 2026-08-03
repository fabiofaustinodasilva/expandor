<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'legal_name' => fake()->company().' LTDA',
            'document' => fake()->numerify('##.###.###/####-##'),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('(##) ####-####'),
            'status' => Company::STATUS_ACTIVE,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => Company::STATUS_SUSPENDED,
        ]);
    }
}
