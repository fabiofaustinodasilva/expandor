<?php

namespace App\Domains\Onboarding\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Actions\GenerateDemoDataAction;

class DemoDataService
{
    public function __construct(
        protected GenerateDemoDataAction $generate,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(Company $company, ?User $actor = null): array
    {
        return $this->generate->execute($company, $actor);
    }
}
