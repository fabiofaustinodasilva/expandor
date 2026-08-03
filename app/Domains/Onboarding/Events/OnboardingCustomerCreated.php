<?php

namespace App\Domains\Onboarding\Events;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnboardingCustomerCreated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public Company $company,
        public ?User $actor = null,
        public array $meta = [],
    ) {}
}
