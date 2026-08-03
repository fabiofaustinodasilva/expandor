<?php

namespace Database\Factories;

use App\Domains\Onboarding\Models\OnboardingStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OnboardingStep> */
class OnboardingStepFactory extends Factory
{
    protected $model = OnboardingStep::class;

    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 20),
            'wizard_key' => $key,
            'training_keywords' => [$key],
            'is_required' => true,
            'is_active' => true,
        ];
    }
}
