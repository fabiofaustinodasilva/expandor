<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class PlatformCompanyAdminService
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    /**
     * @param  array{email?: string, phone?: string|null, whatsapp?: string|null}  $data
     */
    public function updateContact(Company $company, User $admin, User $actor, array $data): User
    {
        $this->assertCompanyUser($company, $admin);

        $old = [
            'email' => $admin->email,
            'phone' => $admin->phone,
            'whatsapp' => $admin->whatsapp,
        ];

        if (isset($data['email'])) {
            $email = strtolower(trim($data['email']));
            $taken = User::query()
                ->withoutGlobalScopes()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('id', '!=', $admin->id)
                ->exists();
            if ($taken) {
                throw ValidationException::withMessages([
                    'email' => ['Este e-mail já está em uso.'],
                ]);
            }
            $admin->email = $email;
        }

        if (array_key_exists('phone', $data)) {
            $admin->phone = $data['phone'];
        }
        if (array_key_exists('whatsapp', $data)) {
            $admin->whatsapp = $data['whatsapp'];
        }

        $admin->save();

        $this->security->recordAudit(
            action: 'platform.company.admin_contact_updated',
            user: $actor,
            auditable: $admin,
            oldValues: $old,
            newValues: [
                'email' => $admin->email,
                'phone' => $admin->phone,
                'whatsapp' => $admin->whatsapp,
            ],
            companyId: $company->id,
        );

        return $admin->fresh();
    }

    public function blockLogin(Company $company, User $admin, User $actor): User
    {
        $this->assertCompanyUser($company, $admin);

        $old = $admin->status;
        $admin->forceFill(['status' => User::STATUS_BLOCKED])->save();
        $this->forceLogout($company, $admin, $actor, audit: false);

        $this->security->recordAudit(
            action: 'platform.company.admin_blocked',
            user: $actor,
            auditable: $admin,
            oldValues: ['status' => $old],
            newValues: ['status' => User::STATUS_BLOCKED],
            companyId: $company->id,
        );

        return $admin->fresh();
    }

    public function unblockLogin(Company $company, User $admin, User $actor): User
    {
        $this->assertCompanyUser($company, $admin);

        $old = $admin->status;
        $admin->forceFill(['status' => User::STATUS_ACTIVE])->save();

        $this->security->recordAudit(
            action: 'platform.company.admin_unblocked',
            user: $actor,
            auditable: $admin,
            oldValues: ['status' => $old],
            newValues: ['status' => User::STATUS_ACTIVE],
            companyId: $company->id,
        );

        return $admin->fresh();
    }

    public function forceLogout(Company $company, User $admin, User $actor, bool $audit = true): void
    {
        $this->assertCompanyUser($company, $admin);

        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $admin->id)->delete();
        }

        PersonalAccessToken::query()->where('tokenable_type', User::class)->where('tokenable_id', $admin->id)->delete();

        if ($audit) {
            $this->security->recordAudit(
                action: 'platform.company.admin_force_logout',
                user: $actor,
                auditable: $admin,
                newValues: ['user_id' => $admin->id],
                companyId: $company->id,
            );
        }
    }

    public function changeAdministrator(Company $company, User $newAdmin, User $actor): User
    {
        $this->assertCompanyUser($company, $newAdmin);

        if ($newAdmin->is_platform_admin) {
            throw ValidationException::withMessages([
                'user_id' => ['Usuário inválido para administrador da empresa.'],
            ]);
        }

        $adminRole = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();
        $managerRole = Role::query()->where('slug', Role::MANAGER)->first()
            ?? Role::query()->where('slug', '!=', Role::PLATFORM_ADMIN)->where('slug', '!=', Role::ADMINISTRATOR)->firstOrFail();

        return DB::transaction(function () use ($company, $newAdmin, $actor, $adminRole, $managerRole) {
            $previousAdmins = User::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('is_platform_admin', false)
                ->whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))
                ->where('id', '!=', $newAdmin->id)
                ->get();

            foreach ($previousAdmins as $previous) {
                $previous->forceFill(['role_id' => $managerRole->id])->save();
            }

            $oldRoleId = $newAdmin->role_id;
            $newAdmin->forceFill(['role_id' => $adminRole->id, 'status' => User::STATUS_ACTIVE])->save();

            $this->security->recordAudit(
                action: 'platform.company.administrator_changed',
                user: $actor,
                auditable: $company,
                oldValues: [
                    'previous_admin_ids' => $previousAdmins->pluck('id')->all(),
                    'new_admin_old_role_id' => $oldRoleId,
                ],
                newValues: [
                    'new_admin_id' => $newAdmin->id,
                    'new_admin_email' => $newAdmin->email,
                ],
                companyId: $company->id,
            );

            return $newAdmin->fresh('role');
        });
    }

    protected function assertCompanyUser(Company $company, User $user): void
    {
        if ($company->isSystem() || (int) $user->company_id !== (int) $company->id || $user->is_platform_admin) {
            throw ValidationException::withMessages([
                'user_id' => ['Usuário não pertence a esta empresa cliente.'],
            ]);
        }
    }
}
