<?php

namespace App\Domains\Payments\DTOs;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Models\Customer;
use Illuminate\Support\Collection;

readonly class SubscriptionOverview
{
    /**
     * @param  Collection<int, \App\Domains\Payments\Models\Payment>  $payments
     * @param  Collection<int, \App\Domains\Payments\Models\Invoice>  $invoices
     * @param  Collection<int, \App\Domains\Payments\Models\PaymentMethod>  $paymentMethods
     */
    public function __construct(
        public Company $company,
        public ?Subscription $subscription,
        public ?Customer $customer,
        public Collection $payments,
        public Collection $invoices,
        public Collection $paymentMethods,
    ) {}
}
