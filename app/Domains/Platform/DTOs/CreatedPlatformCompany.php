<?php

namespace App\Domains\Platform\DTOs;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;

class CreatedPlatformCompany
{
    public function __construct(
        public readonly Company $company,
        public readonly User $administrator,
    ) {}
}
