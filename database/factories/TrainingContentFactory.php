<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Training\Enums\TrainingContentType;
use App\Domains\Training\Models\TrainingCategory;
use App\Domains\Training\Models\TrainingContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingContent>
 */
class TrainingContentFactory extends Factory
{
    protected $model = TrainingContent::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'category_id' => function (array $attributes) {
                return TrainingCategory::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'title' => 'Conteúdo '.fake()->unique()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'type' => TrainingContentType::TEXT,
            'content' => fake()->paragraphs(2, true),
            'url' => null,
            'active' => true,
        ];
    }
}
