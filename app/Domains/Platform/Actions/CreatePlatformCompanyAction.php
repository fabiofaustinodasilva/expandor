<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Platform\DTOs\CreatedPlatformCompany;
use App\Domains\Platform\Services\PlatformCompanyService;

class CreatePlatformCompanyAction
{
    public function __construct(
        protected PlatformCompanyService $companies,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): CreatedPlatformCompany
    {
        return $this->companies->createCompanyWithAdmin($data);
    }
}
