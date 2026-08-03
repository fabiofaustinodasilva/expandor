<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Validation\ValidationException;

class SoftDeleteCompanyAction
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function execute(Company $company, User $actor, ?string $reason = null): Company
    {
        if ($company->isSystem()) {
            throw ValidationException::withMessages([
                'company' => ['Não é permitido excluir a empresa sistema.'],
            ]);
        }

        if ($company->trashed()) {
            throw ValidationException::withMessages([
                'company' => ['Esta empresa já está excluída.'],
            ]);
        }

        $oldStatus = $company->status;

        $company->forceFill([
            'status' => Company::STATUS_CANCELLED,
            'deleted_by' => $actor->id,
            'deletion_reason' => $reason,
        ])->save();

        $company->delete();

        $this->security->recordAudit(
            action: 'platform.company.soft_deleted',
            user: $actor,
            auditable: $company,
            oldValues: ['status' => $oldStatus, 'deleted_at' => null],
            newValues: [
                'status' => Company::STATUS_CANCELLED,
                'deleted_at' => $company->deleted_at?->toIso8601String(),
                'deleted_by' => $actor->id,
                'deletion_reason' => $reason,
            ],
            companyId: $company->id,
        );

        return $company;
    }
}
