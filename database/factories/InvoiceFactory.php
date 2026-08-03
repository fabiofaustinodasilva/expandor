<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => null,
            'subscription_id' => null,
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'status' => InvoiceStatus::Open,
            'amount_due' => 199.90,
            'amount_paid' => 0,
            'currency' => 'BRL',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now()->addDays(7),
            'gateway' => 'fake',
            'gateway_invoice_id' => 'fake_inv_'.fake()->unique()->numerify('######'),
            'metadata' => [],
        ];
    }
}
