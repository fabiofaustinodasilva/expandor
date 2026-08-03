<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Payments\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'document' => '12345678909',
            'phone' => '11999999999',
            'gateway' => 'fake',
            'gateway_customer_id' => 'fake_cus_'.fake()->unique()->numerify('######'),
            'metadata' => [],
        ];
    }
}
