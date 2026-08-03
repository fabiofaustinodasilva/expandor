<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => null,
            'subscription_id' => null,
            'invoice_id' => null,
            'checkout_session_id' => null,
            'amount' => 199.90,
            'currency' => 'BRL',
            'status' => PaymentStatus::Pending,
            'method' => 'pix',
            'gateway' => 'fake',
            'gateway_payment_id' => 'fake_pay_'.fake()->unique()->numerify('######'),
            'raw' => [],
        ];
    }
}
