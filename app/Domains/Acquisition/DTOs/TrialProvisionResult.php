<?php

namespace App\Domains\Acquisition\DTOs;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;

readonly class TrialProvisionResult
{
    public function __construct(
        public Company $company,
        public User $administrator,
        public Subscription $subscription,
        public bool $demoGenerated,
    ) {}
}
