<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Validation\ValidationException;

class ResetCompanyAdminPasswordAction
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function execute(Company $company, User $actor, string $password, ?int $userId = null): User
    {
        if ($company->isSystem()) {
            throw ValidationException::withMessages([
                'company' => ['Não é permitido resetar senha na empresa sistema por este fluxo.'],
            ]);
        }

        $query = User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_platform_admin', false)
            ->whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR));

        if ($userId !== null) {
            $query->where('id', $userId);
        }

        $admin = $query->orderBy('id')->first();

        if ($admin === null) {
            throw ValidationException::withMessages([
                'user_id' => ['Administrador da empresa não encontrado.'],
            ]);
        }

        $admin->forceFill([
            'password' => $password,
        ])->save();

        $this->security->recordAudit(
            action: 'platform.company.admin_password_reset',
            user: $actor,
            auditable: $admin,
            oldValues: null,
            newValues: [
                'target_user_id' => $admin->id,
                'target_email' => $admin->email,
                'password_changed' => true,
            ],
            companyId: $company->id,
        );

        return $admin->fresh();
    }
}
