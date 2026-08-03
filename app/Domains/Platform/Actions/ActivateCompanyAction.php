<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Validation\ValidationException;

class ActivateCompanyAction
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function execute(Company $company, User $actor): Company
    {
        if ($company->isSystem()) {
            throw ValidationException::withMessages([
                'company' => ['Empresa sistema não pode ser alterada por esta ação.'],
            ]);
        }

        $old = $company->status;
        $company->forceFill(['status' => Company::STATUS_ACTIVE])->save();

        $this->security->recordAudit(
            action: 'platform.company.activated',
            user: $actor,
            auditable: $company,
            oldValues: ['status' => $old],
            newValues: ['status' => Company::STATUS_ACTIVE],
            companyId: $company->id,
        );

        return $company->fresh();
    }
}
