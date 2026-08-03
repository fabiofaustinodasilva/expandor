<?php

namespace App\Domains\Security\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Security\Models\DataExportRequest;
use App\Domains\Security\Services\PrivacyService;

class RequestDataExportAction
{
    public function __construct(
        protected PrivacyService $privacy,
    ) {}

    public function execute(Company $company, User $actor, string $format = 'json'): DataExportRequest
    {
        return $this->privacy->requestCompanyExport($company, $actor, $format);
    }
}
