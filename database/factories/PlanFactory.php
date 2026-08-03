<?php

namespace Database\Factories;

use App\Domains\Company\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => fake()->sentence(),
            'price' => 199.90,
            'max_users' => 25,
            'max_properties' => 20000,
            'max_campaigns' => 50,
            'features' => ['maps' => true],
            'status' => Plan::STATUS_ACTIVE,
        ];
    }
}
