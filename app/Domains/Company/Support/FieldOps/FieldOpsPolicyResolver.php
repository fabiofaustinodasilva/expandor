<?php

namespace App\Domains\Company\Support\FieldOps;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use Illuminate\Support\Facades\DB;

/**
 * Resolve e persiste políticas de operação de campo.
 *
 * Extensão futura por campanha: passar $campaignId e ler overrides
 * (ex.: campaign_settings) antes do fallback da empresa — sem mudar callers.
 */
class FieldOpsPolicyResolver
{
    public function resolve(?int $companyId, ?int $campaignId = null): FieldOpsPolicy
    {
        // Futuro: if ($campaignId) { $override = ...; if ($override) return $override; }
        unset($campaignId);

        $settings = $this->loadCompanySettings($companyId);

        return new FieldOpsPolicy(
            visibility: $this->enumOr(
                PointsVisibility::class,
                $settings[FieldOpsKeys::POINTS_VISIBILITY] ?? null,
                PointsVisibility::Company
            ),
            display: $this->enumOr(
                PointsDisplay::class,
                $settings[FieldOpsKeys::POINTS_DISPLAY] ?? null,
                PointsDisplay::AllWithFilters
            ),
            editOthers: $this->enumOr(
                PointEditOthersPolicy::class,
                $settings[FieldOpsKeys::POINTS_EDIT_OTHERS] ?? null,
                PointEditOthersPolicy::Nobody
            ),
            delete: $this->enumOr(
                PointDeletePolicy::class,
                $settings[FieldOpsKeys::POINTS_DELETE] ?? null,
                PointDeletePolicy::Creator
            ),
            scope: 'company',
            campaignId: null,
        );
    }

    public function resolveForUser(User $user, ?int $campaignId = null): FieldOpsPolicy
    {
        return $this->resolve($user->company_id ? (int) $user->company_id : null, $campaignId);
    }

    /**
     * @param  array{
     *     points_visibility?: string,
     *     points_display?: string,
     *     points_edit_others?: string,
     *     points_delete?: string
     * }  $data
     */
    public function saveForCompany(Company $company, array $data, ?User $actor = null): FieldOpsPolicy
    {
        $map = [
            FieldOpsKeys::POINTS_VISIBILITY => $data['points_visibility'] ?? PointsVisibility::Company->value,
            FieldOpsKeys::POINTS_DISPLAY => $data['points_display'] ?? PointsDisplay::AllWithFilters->value,
            FieldOpsKeys::POINTS_EDIT_OTHERS => $data['points_edit_others'] ?? PointEditOthersPolicy::Nobody->value,
            FieldOpsKeys::POINTS_DELETE => $data['points_delete'] ?? PointDeletePolicy::Creator->value,
        ];

        DB::transaction(function () use ($company, $map): void {
            foreach ($map as $key => $value) {
                CompanySetting::query()->updateOrCreate(
                    ['company_id' => $company->id, 'key' => $key],
                    ['value' => $value]
                );
            }
        });

        unset($actor);

        return $this->resolve((int) $company->id);
    }

    /**
     * IDs de created_by permitidos. null = todos da empresa (tenancy já aplica).
     *
     * @return list<int>|null
     */
    public function visibleCreatorIds(User $user, FieldOpsPolicy $policy): ?array
    {
        $slug = $user->role?->slug;
        if (in_array($slug, [Role::ADMINISTRATOR, Role::MANAGER], true)) {
            return null;
        }

        return match ($policy->visibility) {
            PointsVisibility::Company => null,
            PointsVisibility::Own => [(int) $user->id],
            PointsVisibility::Team => $this->teamMemberIds($user),
        };
    }

    /**
     * @return list<int>
     */
    public function teamMemberIds(User $user): array
    {
        $user->loadMissing('teams.users');

        $ids = [(int) $user->id];
        foreach ($user->teams as $team) {
            foreach ($team->users as $member) {
                $ids[] = (int) $member->id;
            }
            if ($team->supervisor_id) {
                $ids[] = (int) $team->supervisor_id;
            }
        }

        // Supervisor sem membership: times que supervisiona
        if ($user->role?->slug === Role::SUPERVISOR) {
            $supervised = \App\Domains\Company\Models\Team::query()
                ->where('supervisor_id', $user->id)
                ->with('users:id')
                ->get();
            foreach ($supervised as $team) {
                foreach ($team->users as $member) {
                    $ids[] = (int) $member->id;
                }
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));

        return $ids === [] ? [(int) $user->id] : $ids;
    }

    public function canEditProperty(User $user, Property $property, FieldOpsPolicy $policy): bool
    {
        if ((int) $user->company_id !== (int) $property->company_id) {
            return false;
        }

        if (! $user->hasPermission('properties.manage') && ! $user->hasPermission('properties.update')) {
            return false;
        }

        $slug = $user->role?->slug;
        if (in_array($slug, [Role::ADMINISTRATOR, Role::MANAGER], true)) {
            return true;
        }

        $ownerId = $property->ownerUserId();
        $isOwner = $ownerId !== null && (int) $ownerId === (int) $user->id;

        if ($isOwner) {
            return true;
        }

        return $this->roleMeetsEditOthers($user, $policy->editOthers);
    }

    public function canAdjustProperty(User $user, Property $property, FieldOpsPolicy $policy): bool
    {
        if ((int) $user->company_id !== (int) $property->company_id) {
            return false;
        }

        $canSkill = $user->hasPermission('properties.manage')
            || $user->hasPermission('properties.adjust')
            || $user->hasPermission('properties.update');

        if (! $canSkill) {
            return false;
        }

        $slug = $user->role?->slug;
        if (in_array($slug, [Role::ADMINISTRATOR, Role::MANAGER], true)) {
            return true;
        }

        $ownerId = $property->ownerUserId();
        $isOwner = $ownerId !== null && (int) $ownerId === (int) $user->id;

        // Regra operacional: criador sempre pode ajustar o próprio ponto.
        if ($isOwner) {
            return true;
        }

        return $this->roleMeetsEditOthers($user, $policy->editOthers);
    }

    public function canDeleteProperty(User $user, Property $property, FieldOpsPolicy $policy): bool
    {
        if ((int) $user->company_id !== (int) $property->company_id) {
            return false;
        }

        if ($policy->delete === PointDeletePolicy::Nobody) {
            return false;
        }

        $canSkill = $user->hasPermission('properties.manage')
            || $user->hasPermission('properties.delete');

        if (! $canSkill) {
            return false;
        }

        $ownerId = $property->ownerUserId();
        $isOwner = $ownerId !== null && (int) $ownerId === (int) $user->id;
        $slug = $user->role?->slug;

        // Administrador sempre pode excluir (exceto política Nobody).
        if ($slug === Role::ADMINISTRATOR) {
            return true;
        }

        return match ($policy->delete) {
            PointDeletePolicy::Nobody => false,
            PointDeletePolicy::Creator => $isOwner,
            PointDeletePolicy::Supervisor => $isOwner
                || in_array($slug, [Role::SUPERVISOR, Role::MANAGER], true),
            PointDeletePolicy::Manager => $slug === Role::MANAGER,
            PointDeletePolicy::Administrator => false, // só admin, já retornou acima
        };
    }

    /**
     * @param  class-string<\BackedEnum>  $enum
     * @param  mixed  $value
     */
    protected function enumOr(string $enum, mixed $value, \BackedEnum $default): \BackedEnum
    {
        if (! is_string($value) || $value === '') {
            return $default;
        }

        try {
            return $enum::from($value);
        } catch (\ValueError) {
            return $default;
        }
    }

    /**
     * @return array<string, string|null>
     */
    protected function loadCompanySettings(?int $companyId): array
    {
        if (! $companyId) {
            return [];
        }

        return CompanySetting::query()
            ->where('company_id', $companyId)
            ->whereIn('key', FieldOpsKeys::all())
            ->pluck('value', 'key')
            ->all();
    }

    protected function roleMeetsEditOthers(User $user, PointEditOthersPolicy $policy): bool
    {
        $slug = $user->role?->slug;

        return match ($policy) {
            PointEditOthersPolicy::Nobody => false,
            PointEditOthersPolicy::Supervisor => in_array($slug, [Role::SUPERVISOR, Role::MANAGER, Role::ADMINISTRATOR], true),
            PointEditOthersPolicy::Manager => in_array($slug, [Role::MANAGER, Role::ADMINISTRATOR], true),
            PointEditOthersPolicy::Administrator => $slug === Role::ADMINISTRATOR,
        };
    }
}
