<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\CRM\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PipelineStage>
 */
class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'company_id' => Company::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'position' => fake()->numberBetween(1, 10),
            'color' => '#3b82f6',
            'is_won' => false,
            'is_lost' => false,
            'is_active' => true,
        ];
    }
}
