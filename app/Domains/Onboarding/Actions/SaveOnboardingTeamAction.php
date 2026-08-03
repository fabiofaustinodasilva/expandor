<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Company\Services\UserService;
use App\Domains\Onboarding\Events\OnboardingTeamCompleted;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveOnboardingTeamAction
{
    public function __construct(
        protected SaasOnboardingService $saasOnboarding,
        protected UserService $users,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Company $company, array $data, ?User $actor = null): User
    {
        $allowed = $this->saasOnboarding->allowedRoles();
        $roleSlug = (string) ($data['role'] ?? Role::SELLER);

        if (! array_key_exists($roleSlug, $allowed)) {
            throw ValidationException::withMessages([
                'role' => ['Perfil não permitido neste onboarding.'],
            ]);
        }

        $roleId = Role::query()->where('slug', $roleSlug)->value('id');
        if ($roleId === null) {
            throw ValidationException::withMessages([
                'role' => ['Perfil inválido.'],
            ]);
        }

        return DB::transaction(function () use ($company, $data, $actor, $roleId, $roleSlug) {
            $email = strtolower(trim((string) $data['email']));

            $existing = User::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($existing !== null) {
                $user = $this->users->update($existing, [
                    'role_id' => $roleId,
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => $data['password'] ?? null,
                    'status' => User::STATUS_ACTIVE,
                ], $actor);
            } else {
                $user = $this->users->create([
                    'role_id' => $roleId,
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => $data['password'] ?? 'Password123!',
                    'status' => User::STATUS_ACTIVE,
                ], $company, $actor);
            }

            $company = $this->saasOnboarding->advanceTo($company, SaasOnboardingService::STEP_CUSTOMER);
            OnboardingTeamCompleted::dispatch($company, $actor, [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $roleSlug,
            ]);

            return $user;
        });
    }
}
