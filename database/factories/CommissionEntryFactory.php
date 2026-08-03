<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Enums\CommissionStatus;
use App\Domains\CRM\Models\CommissionEntry;
use App\Domains\CRM\Models\Opportunity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionEntry>
 */
class CommissionEntryFactory extends Factory
{
    protected $model = CommissionEntry::class;

    public function definition(): array
    {
        $base = fake()->randomFloat(2, 100, 2000);
        $percent = fake()->randomFloat(2, 1, 10);

        return [
            'company_id' => Company::factory(),
            'opportunity_id' => function (array $attributes) {
                return Opportunity::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'user_id' => function (array $attributes) {
                return User::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'commission_rule_id' => null,
            'base_amount' => $base,
            'percent' => $percent,
            'commission_amount' => round($base * ($percent / 100), 2),
            'status' => CommissionStatus::PENDING,
            'calculated_at' => now(),
        ];
    }
}
