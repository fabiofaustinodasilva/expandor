<?php

namespace App\Domains\Onboarding\Events;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnboardingCompanyCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Company $company,
        public ?User $actor = null,
    ) {}
}
