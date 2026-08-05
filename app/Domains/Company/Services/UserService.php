<?php

namespace App\Domains\Company\Services;

use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Billing\Exceptions\PlanLimitExceededException;
use App\Domains\Billing\Services\BillingService;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Media\Enums\MediaCategory;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected BillingService $billing,
        protected SecurityService $security,
        protected MediaUploadService $media,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, Company $company, ?User $actor = null): User
    {
        try {
            $this->billing->assertWithinLimit(UsageMetric::USERS, 1, $company);
        } catch (PlanLimitExceededException $exception) {
            throw ValidationException::withMessages([
                'email' => [$exception->getMessage()],
            ]);
        }

        $email = app(\App\Domains\Security\Services\RegistrationIntegrityService::class)
            ->normalizeEmail((string) $data['email']);

        app(\App\Domains\Security\Services\RegistrationIntegrityService::class)
            ->assertEmailAvailable($email, 'email');

        $user = User::query()->create([
            'company_id' => $company->id,
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => $data['status'] ?? User::STATUS_ACTIVE,
        ]);

        $this->security->recordAudit(
            'team.member_created',
            $actor,
            $user,
            null,
            [
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'status' => $user->status,
            ],
            companyId: $company->id,
        );

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?User $actor = null): User
    {
        $oldRoleId = (int) $user->role_id;
        $old = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role_id' => $user->role_id,
            'status' => $user->status,
        ];

        $email = app(\App\Domains\Security\Services\RegistrationIntegrityService::class)
            ->normalizeEmail((string) $data['email']);

        app(\App\Domains\Security\Services\RegistrationIntegrityService::class)
            ->assertEmailAvailable($email, 'email', $user->id);

        $payload = [
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? $user->status,
        ];

        $passwordChanged = ! empty($data['password']);
        if ($passwordChanged) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->refresh();

        $this->security->recordAudit(
            'team.member_updated',
            $actor,
            $user,
            $old,
            [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role_id' => $user->role_id,
                'status' => $user->status,
            ],
            companyId: $user->company_id,
        );

        if ($oldRoleId !== (int) $user->role_id) {
            $this->security->recordAudit(
                'team.profile_changed',
                $actor,
                $user,
                ['role_id' => $oldRoleId],
                ['role_id' => $user->role_id],
                companyId: $user->company_id,
            );
        }

        if ($passwordChanged) {
            $this->security->recordAudit(
                'team.password_reset',
                $actor,
                $user,
                null,
                ['via' => 'manual_password'],
                companyId: $user->company_id,
            );
        }

        return $user;
    }

    public function toggleStatus(User $actor, User $user): User
    {
        if ($actor->id === $user->id) {
            throw ValidationException::withMessages([
                'status' => ['Você não pode alterar o próprio status.'],
            ]);
        }

        $previous = $user->status;
        $next = $user->status === User::STATUS_ACTIVE
            ? User::STATUS_INACTIVE
            : User::STATUS_ACTIVE;

        $user->update(['status' => $next]);
        $user->refresh();

        $this->security->recordAudit(
            $next === User::STATUS_INACTIVE ? 'team.member_deactivated' : 'team.member_activated',
            $actor,
            $user,
            ['status' => $previous],
            ['status' => $next],
            companyId: $user->company_id,
        );

        return $user;
    }

    /**
     * Gera senha temporária e registra auditoria.
     */
    public function resetTemporaryPassword(User $actor, User $user): string
    {
        if ($actor->id === $user->id) {
            throw ValidationException::withMessages([
                'password' => ['Use outra tela para alterar a própria senha.'],
            ]);
        }

        $temporary = Str::password(10, symbols: false);

        $user->update([
            'password' => Hash::make($temporary),
        ]);

        $this->security->recordAudit(
            'team.password_reset',
            $actor,
            $user,
            null,
            ['via' => 'temporary_password'],
            companyId: $user->company_id,
        );

        return $temporary;
    }

    /**
     * Sincroniza overrides individuais a partir do estado desejado (checkbox comercial).
     * desired[slug] = true|false → grant/deny/inherit relativo à role base.
     *
     * @param  array<string, bool>  $desiredBySlug
     */
    public function syncPermissionOverrides(User $actor, User $user, array $desiredBySlug): User
    {
        $user->loadMissing(['role.permissions', 'permissionOverrides']);
        \App\Domains\Company\Support\CommercialProfileCatalog::ensurePermissionRecords();

        $allowed = array_flip(\App\Domains\Company\Support\CommercialProfileCatalog::commercialPermissionSlugs());
        $permissions = \App\Domains\Company\Models\Permission::query()
            ->whereIn('slug', array_keys($allowed))
            ->get()
            ->keyBy('slug');

        $current = [];
        foreach ($user->permissionOverrides as $perm) {
            $current[$perm->slug] = $perm->pivot->effect;
        }

        foreach ($desiredBySlug as $slug => $wantGranted) {
            if (! isset($allowed[$slug]) || ! $permissions->has($slug)) {
                continue;
            }

            $wantGranted = (bool) $wantGranted;
            $base = $this->roleBaseGrants($user, $slug);
            $permission = $permissions->get($slug);
            $previous = $current[$slug] ?? null;

            if ($wantGranted === $base) {
                // Volta a herdar o perfil
                if ($previous !== null) {
                    $user->permissionOverrides()->detach($permission->id);
                    unset($current[$slug]);
                    $this->security->recordAudit(
                        'team.permission_override_cleared',
                        $actor,
                        $user,
                        ['permission' => $slug, 'effect' => $previous],
                        ['permission' => $slug, 'effect' => 'inherit'],
                        companyId: $user->company_id,
                    );
                }
                continue;
            }

            $effect = $wantGranted
                ? \App\Domains\Company\Support\CommercialProfileCatalog::EFFECT_GRANT
                : \App\Domains\Company\Support\CommercialProfileCatalog::EFFECT_DENY;

            if ($previous === $effect) {
                continue;
            }

            $user->permissionOverrides()->syncWithoutDetaching([
                $permission->id => ['effect' => $effect],
            ]);
            $current[$slug] = $effect;

            $this->security->recordAudit(
                $effect === \App\Domains\Company\Support\CommercialProfileCatalog::EFFECT_GRANT
                    ? 'team.permission_override_granted'
                    : 'team.permission_override_denied',
                $actor,
                $user,
                ['permission' => $slug, 'effect' => $previous ?? 'inherit'],
                ['permission' => $slug, 'effect' => $effect],
                companyId: $user->company_id,
            );
        }

        return $user->refresh()->load(['role.permissions', 'permissionOverrides']);
    }

    protected function roleBaseGrants(User $user, string $slug): bool
    {
        if ($user->roleHasPermission($slug)) {
            return true;
        }

        foreach (\App\Domains\Company\Support\CommercialProfileCatalog::coarseParents()[$slug] ?? [] as $parent) {
            if ($user->roleHasPermission($parent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCompany(Company $company, array $data): Company
    {
        return DB::transaction(function () use ($company, $data) {
            $company->update([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'document' => $data['document'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'address' => $data['address'] ?? null,
                'segment' => $data['segment'] ?? null,
            ]);

            return $company->refresh();
        });
    }

    /**
     * Atualiza o próprio perfil (sem permissões/roles).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateOwnProfile(User $user, array $data, $photo = null, bool $removePhoto = false): User
    {
        return DB::transaction(function () use ($user, $data, $photo, $removePhoto) {
            $payload = [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
            ];

            if (! empty($data['password'])) {
                // Cast `hashed` no model aplica o hash.
                $payload['password'] = $data['password'];
            }

            if ($photo instanceof UploadedFile) {
                $result = $this->media->store(
                    $photo,
                    (int) $user->company_id,
                    MediaCategory::Profiles,
                    MediaPurpose::ProfilePhoto,
                    $user->photo,
                    $user->photo_thumb,
                    'photo',
                );
                $payload['photo'] = $result->path;
                $payload['photo_thumb'] = $result->thumbPath;
            } elseif ($removePhoto) {
                $this->media->delete($user->photo, $user->photo_thumb);
                $payload['photo'] = null;
                $payload['photo_thumb'] = null;
            }

            $user->update($payload);

            $this->security->recordAudit(
                'profile.updated',
                $user,
                $user,
                null,
                [
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'whatsapp' => $user->whatsapp,
                    'password_changed' => ! empty($data['password']),
                    'photo_changed' => isset($payload['photo']) || $removePhoto,
                ],
            );

            return $user->refresh();
        });
    }
}
