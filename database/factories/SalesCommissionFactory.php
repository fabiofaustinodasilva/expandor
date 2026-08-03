<?php

namespace Database\Factories;

use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Visits\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesCommission> */
class SalesCommissionFactory extends Factory
{
    protected $model = SalesCommission::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'visit_id' => Visit::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(2, true),
            'commission_amount' => fake()->randomFloat(2, 10, 200),
            'quantity' => 1,
            'status' => SalesCommissionStatus::PENDING,
            'earned_at' => now(),
        ];
    }
}
