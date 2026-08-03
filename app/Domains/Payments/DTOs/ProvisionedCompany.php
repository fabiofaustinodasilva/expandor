<?php

namespace App\Domains\Payments\DTOs;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer;

readonly class ProvisionedCompany
{
    public function __construct(
        public Company $company,
        public User $administrator,
        public Customer $customer,
        public CheckoutSession $checkout,
        public string $plainPassword,
    ) {}
}
