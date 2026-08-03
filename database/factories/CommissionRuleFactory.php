<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\CRM\Models\CommissionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionRule>
 */
class CommissionRuleFactory extends Factory
{
    protected $model = CommissionRule::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Regra '.fake()->unique()->words(2, true),
            'percent' => fake()->randomFloat(2, 1, 15),
            'min_amount' => 0,
            'is_active' => true,
        ];
    }
}
