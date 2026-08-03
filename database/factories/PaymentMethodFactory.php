<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Payments\Enums\PaymentMethodType;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'type' => PaymentMethodType::Pix,
            'gateway' => 'fake',
            'gateway_payment_method_id' => 'fake_pm_'.fake()->unique()->numerify('######'),
            'brand' => null,
            'last_four' => null,
            'is_default' => true,
            'status' => 'active',
            'metadata' => [],
        ];
    }
}
