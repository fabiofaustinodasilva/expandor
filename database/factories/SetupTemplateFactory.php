<?php

namespace Database\Factories;

use App\Domains\Onboarding\Models\SetupTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SetupTemplate> */
class SetupTemplateFactory extends Factory
{
    protected $model = SetupTemplate::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'payload' => ['demo' => true],
            'is_active' => true,
        ];
    }
}
