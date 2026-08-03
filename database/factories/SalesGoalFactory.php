<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Enums\GoalPeriod;
use App\Domains\CRM\Models\SalesGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesGoal>
 */
class SalesGoalFactory extends Factory
{
    protected $model = SalesGoal::class;

    public function definition(): array
    {
        $start = now()->startOfMonth();

        return [
            'company_id' => Company::factory(),
            'user_id' => function (array $attributes) {
                return User::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'period_type' => GoalPeriod::MONTHLY,
            'period_start' => $start->toDateString(),
            'period_end' => $start->copy()->endOfMonth()->toDateString(),
            'target_amount' => fake()->randomFloat(2, 1000, 20000),
            'target_count' => fake()->numberBetween(5, 50),
        ];
    }
}
