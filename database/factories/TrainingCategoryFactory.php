<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Training\Models\TrainingCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingCategory>
 */
class TrainingCategoryFactory extends Factory
{
    protected $model = TrainingCategory::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Categoria '.fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'active' => true,
        ];
    }
}
