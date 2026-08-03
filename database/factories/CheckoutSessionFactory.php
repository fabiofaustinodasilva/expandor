<?php

namespace Database\Factories;

use App\Domains\Company\Models\Plan;
use App\Domains\Payments\Enums\BillingCycle;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\CheckoutSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CheckoutSession>
 */
class CheckoutSessionFactory extends Factory
{
    protected $model = CheckoutSession::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'plan_id' => Plan::factory(),
            'company_id' => null,
            'customer_id' => null,
            'status' => CheckoutStatus::Pending,
            'gateway' => 'fake',
            'gateway_session_id' => 'fake_sess_'.Str::random(10),
            'checkout_url' => 'https://payments.example/checkout/fake',
            'buyer_name' => fake()->name(),
            'buyer_email' => fake()->unique()->safeEmail(),
            'buyer_document' => '12345678909',
            'buyer_phone' => '11999999999',
            'company_name' => fake()->company(),
            'amount' => 199.90,
            'currency' => 'BRL',
            'billing_cycle' => BillingCycle::Monthly,
            'expires_at' => now()->addHour(),
            'payload' => [],
        ];
    }
}
