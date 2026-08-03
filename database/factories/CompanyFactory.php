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
            // Testes / empresas “antigas”: fora do funil premium por padrão.
            'onboarding_status' => Company::ONBOARDING_COMPLETED,
            'onboarding_step' => 5,
            'onboarding_completed_at' => now(),
        ];
    }

    public function pendingOnboarding(): static
    {
        return $this->state(fn () => [
            'onboarding_status' => Company::ONBOARDING_PENDING,
            'onboarding_step' => 1,
            'onboarding_completed_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => Company::STATUS_SUSPENDED,
        ]);
    }
}
