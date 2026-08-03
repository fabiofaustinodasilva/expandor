<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Validation\ValidationException;

class UpdatePlatformCompanyAction
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     legal_name?: string|null,
     *     document?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     whatsapp?: string|null,
     *     address?: string|null,
     *     segment?: string|null,
     * }  $data
     */
    public function execute(Company $company, User $actor, array $data): Company
    {
        if ($company->isSystem()) {
            throw ValidationException::withMessages([
                'company' => ['Não é permitido editar a empresa sistema.'],
            ]);
        }

        if ($company->trashed()) {
            throw ValidationException::withMessages([
                'company' => ['Restaure a empresa antes de editar.'],
            ]);
        }

        $old = $company->only([
            'name', 'legal_name', 'document', 'email', 'phone', 'whatsapp', 'address', 'segment',
        ]);

        $company->fill([
            'name' => $data['name'],
            'legal_name' => $data['legal_name'] ?? null,
            'document' => $data['document'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'address' => $data['address'] ?? null,
            'segment' => $data['segment'] ?? null,
        ])->save();

        $this->security->recordAudit(
            action: 'platform.company.updated',
            user: $actor,
            auditable: $company,
            oldValues: $old,
            newValues: $company->only(array_keys($old)),
            companyId: $company->id,
        );

        return $company->fresh();
    }
}
