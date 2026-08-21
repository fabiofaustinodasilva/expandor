<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Support\BillingSuspensionReasons;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Validation\ValidationException;

class SuspendCompanyAction
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function execute(Company $company, User $actor, ?string $reason = null): Company
    {
        if ($company->isSystem()) {
            throw ValidationException::withMessages([
                'company' => ['Não é permitido suspender a empresa sistema.'],
            ]);
        }

        if ($company->trashed()) {
            throw ValidationException::withMessages([
                'company' => ['Não é possível suspender uma empresa excluída.'],
            ]);
        }

        $old = $company->status;
        $company->forceFill([
            'status' => Company::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'suspension_reason' => BillingSuspensionReasons::ADMINISTRATIVE,
        ])->save();

        $this->security->recordAudit(
            action: 'platform.company.suspended',
            user: $actor,
            auditable: $company,
            oldValues: ['status' => $old],
            newValues: [
                'status' => Company::STATUS_SUSPENDED,
                'reason' => $reason ?: BillingSuspensionReasons::ADMINISTRATIVE,
                'suspension_reason' => BillingSuspensionReasons::ADMINISTRATIVE,
            ],
            companyId: $company->id,
        );

        return $company->fresh();
    }
}
