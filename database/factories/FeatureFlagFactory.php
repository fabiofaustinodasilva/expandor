<?php

namespace Database\Factories;

use App\Domains\Platform\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<FeatureFlag> */
class FeatureFlagFactory extends Factory
{
    protected $model = FeatureFlag::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'key' => Str::slug($name),
            'name' => ucfirst($name),
            'description' => fake()->sentence(),
            'default_enabled' => false,
            'is_active' => true,
        ];
    }
}
