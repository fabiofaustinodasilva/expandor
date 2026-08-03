<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Models\TrainingProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingProgress>
 */
class TrainingProgressFactory extends Factory
{
    protected $model = TrainingProgress::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => function (array $attributes) {
                return User::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'training_content_id' => function (array $attributes) {
                return TrainingContent::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'started_at' => now(),
            'completed_at' => null,
        ];
    }
}
