<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Platform\Enums\HealthRiskLevel;
use App\Domains\Platform\Models\CompanyHealthScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompanyHealthScore> */
class CompanyHealthScoreFactory extends Factory
{
    protected $model = CompanyHealthScore::class;

    public function definition(): array
    {
        $score = fake()->numberBetween(20, 100);

        return [
            'company_id' => Company::factory(),
            'score' => $score,
            'risk_level' => HealthRiskLevel::fromScore($score),
            'factors' => ['users_count' => 3],
            'calculated_at' => now(),
        ];
    }
}
