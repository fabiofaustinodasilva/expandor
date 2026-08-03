<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Validation\ValidationException;

class RestoreCompanyAction
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function execute(Company $company, User $actor): Company
    {
        if ($company->isSystem()) {
            throw ValidationException::withMessages([
                'company' => ['Não é permitido restaurar a empresa sistema.'],
            ]);
        }

        if (! $company->trashed()) {
            throw ValidationException::withMessages([
                'company' => ['Esta empresa não está excluída.'],
            ]);
        }

        $previousDeletedAt = $company->deleted_at?->toIso8601String();
        $previousStatus = $company->status;

        $company->restore();

        $company->forceFill([
            'status' => Company::STATUS_SUSPENDED,
            'deleted_by' => null,
            'deletion_reason' => null,
        ])->save();

        $this->security->recordAudit(
            action: 'platform.company.restored',
            user: $actor,
            auditable: $company,
            oldValues: ['deleted_at' => $previousDeletedAt, 'status' => $previousStatus],
            newValues: [
                'deleted_at' => null,
                'status' => Company::STATUS_SUSPENDED,
            ],
            companyId: $company->id,
        );

        return $company->fresh();
    }
}
