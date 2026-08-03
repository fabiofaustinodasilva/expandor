<?php

namespace App\Domains\Payments\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\Invoice;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Invoice
    {
        if (empty($attributes['number'])) {
            $attributes['number'] = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        }

        return Invoice::query()->withoutGlobalScopes()->create($attributes);
    }

    public function createForCheckout(
        Company $company,
        Customer $customer,
        Subscription $subscription,
        float $amount,
        string $currency,
        string $gateway,
        ?string $gatewayInvoiceId = null,
    ): Invoice {
        return $this->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'status' => InvoiceStatus::Paid,
            'amount_due' => $amount,
            'amount_paid' => $amount,
            'currency' => $currency,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now(),
            'paid_at' => now(),
            'gateway' => $gateway,
            'gateway_invoice_id' => $gatewayInvoiceId,
        ]);
    }

    public function markPaid(Invoice $invoice, float $amountPaid): Invoice
    {
        $invoice->forceFill([
            'status' => InvoiceStatus::Paid,
            'amount_paid' => $amountPaid,
            'paid_at' => now(),
        ])->save();

        return $invoice->fresh();
    }
}
