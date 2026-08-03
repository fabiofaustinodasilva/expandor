<?php

namespace App\Domains\Company\Support;

use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;

/**
 * Camada comercial: permissões administrativas editáveis + núcleo operacional fixo.
 *
 * Role = perfil base. permission_user = deltas só sobre slugs administrativos.
 * Resolução efetiva: deny > grant > role (+ herança de pai grosseiro).
 * Slugs de operação de campo NÃO entram na UI e ignoram deny (vendedor não perde o fluxo).
 */
final class CommercialProfileCatalog
{
    public const EFFECT_GRANT = 'grant';

    public const EFFECT_DENY = 'deny';

    public const EFFECT_INHERIT = 'inherit';

    /**
     * @return list<string>
     */
    public static function commercialRoleSlugs(): array
    {
        return [Role::SELLER, Role::SUPERVISOR, Role::MANAGER];
    }

    /**
     * @return array<string, string>
     */
    public static function profileLabels(): array
    {
        return [
            Role::SELLER => 'Vendedor',
            Role::SUPERVISOR => 'Supervisor',
            Role::MANAGER => 'Gerente Comercial',
        ];
    }

    public static function labelForSlug(?string $slug): string
    {
        if ($slug === null) {
            return '—';
        }

        return self::profileLabels()[$slug] ?? $slug;
    }

    /**
     * Permissões administrativas exibidas na Central da Equipe.
     *
     * @return array<string, list<array{key: string, label: string, permission: string, hint?: string}>>
     */
    public static function permissionGroups(): array
    {
        return [
            'EQUIPE' => [
                ['key' => 'team_create', 'label' => 'Criar vendedor', 'permission' => 'users.create'],
                ['key' => 'team_edit', 'label' => 'Editar vendedor', 'permission' => 'users.update'],
                ['key' => 'team_deactivate', 'label' => 'Inativar vendedor', 'permission' => 'users.deactivate'],
                ['key' => 'team_reset', 'label' => 'Resetar senha', 'permission' => 'users.reset_password'],
                ['key' => 'team_perms', 'label' => 'Gerenciar permissões', 'permission' => 'users.manage_permissions'],
            ],
            'CAMPANHAS' => [
                ['key' => 'campaigns_create', 'label' => 'Criar', 'permission' => 'campaigns.create'],
                ['key' => 'campaigns_edit', 'label' => 'Editar', 'permission' => 'campaigns.update'],
                ['key' => 'campaigns_close', 'label' => 'Encerrar', 'permission' => 'campaigns.close'],
            ],
            'RESULTADOS' => [
                ['key' => 'results_team', 'label' => 'Ver resultados da equipe', 'permission' => 'reports.view'],
                ['key' => 'results_export', 'label' => 'Exportar', 'permission' => 'reports.export'],
            ],
            'COMERCIAL' => [
                ['key' => 'commissions_manage', 'label' => 'Gerenciar comissões e produtos', 'permission' => 'commissions.manage'],
            ],
            'SISTEMA' => [
                ['key' => 'cfg_settings', 'label' => 'Configurações', 'permission' => 'company.manage'],
                ['key' => 'cfg_integrations', 'label' => 'Integrações', 'permission' => 'integrations.view'],
                ['key' => 'cfg_dashboard', 'label' => 'Dashboard Gerencial', 'permission' => 'dashboard.team'],
            ],
        ];
    }

    /**
     * Capacidades de campo garantidas pelo Role — não editáveis na UI de permissões.
     *
     * @return list<string>
     */
    public static function operationalCoreSlugs(): array
    {
        return [
            'maps.view',
            'maps.team_view',
            'properties.view',
            'properties.create',
            'properties.update',
            'properties.adjust',
            'properties.delete',
            'properties.notes',
            'properties.address',
            'properties.manage',
            'visits.view',
            'visits.manage',
            'visits.return',
            'visits.contract',
            'visits.status',
            'residents.view',
            'residents.manage',
            'campaigns.view',
            'dashboard.view',
            'sales_app.access',
            'crm.view',
            'crm.manage',
            'commissions.view_self',
        ];
    }

    public static function isOperationalCore(string $slug): bool
    {
        return in_array($slug, self::operationalCoreSlugs(), true);
    }

    /**
     * Filhos finos → pai grosseiro (compatibilidade com checagens legadas).
     *
     * @return array<string, list<string>>
     */
    public static function coarseParents(): array
    {
        return [
            'users.create' => ['users.manage'],
            'users.update' => ['users.manage'],
            'users.deactivate' => ['users.manage'],
            'users.reset_password' => ['users.manage'],
            'users.manage_permissions' => ['users.manage'],
            'properties.create' => ['properties.manage'],
            'properties.update' => ['properties.manage'],
            'properties.adjust' => ['properties.manage'],
            'properties.delete' => ['properties.manage'],
            'properties.notes' => ['properties.manage'],
            'properties.address' => ['properties.manage'],
            'maps.team_view' => ['maps.view', 'maps.manage'],
            'visits.return' => ['visits.manage'],
            'visits.contract' => ['visits.manage'],
            'visits.status' => ['visits.manage'],
            'campaigns.create' => ['campaigns.manage'],
            'campaigns.update' => ['campaigns.manage'],
            'campaigns.close' => ['campaigns.manage'],
            'reports.export' => ['reports.view'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function commercialPermissionSlugs(): array
    {
        $slugs = [];
        foreach (self::permissionGroups() as $items) {
            foreach ($items as $item) {
                $slugs[] = $item['permission'];
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * @return array{mode: string, label: string, overrides_count: int}
     */
    public static function overrideSummary(User $user): array
    {
        $user->loadMissing('permissionOverrides');
        $adminSlugs = array_flip(self::commercialPermissionSlugs());
        $count = 0;
        foreach ($user->permissionOverrides as $perm) {
            if (isset($adminSlugs[$perm->slug])) {
                $count++;
            }
        }

        if ($count === 0) {
            return [
                'mode' => 'role_default',
                'label' => 'Perfil padrão',
                'overrides_count' => 0,
            ];
        }

        return [
            'mode' => 'customized',
            'label' => 'Permissões personalizadas',
            'overrides_count' => $count,
        ];
    }

    /**
     * Descrição efetiva para a UI (com estado de override).
     *
     * @return array<string, list<array{key: string, label: string, permission: string, granted: bool, effect: string}>>
     */
    public static function describeEffectiveForUser(User $user): array
    {
        $out = [];
        foreach (self::permissionGroups() as $category => $items) {
            $out[$category] = [];
            foreach ($items as $item) {
                $slug = $item['permission'];
                $out[$category][] = [
                    'key' => $item['key'],
                    'label' => $item['label'],
                    'permission' => $slug,
                    'granted' => $user->hasPermission($slug),
                    'effect' => $user->permissionOverrideEffect($slug) ?? self::EFFECT_INHERIT,
                ];
            }
        }

        return $out;
    }

    /**
     * @deprecated Use describeEffectiveForUser
     *
     * @param  iterable<int, object{slug?: string}|string>  $permissionSlugs
     * @return array<string, list<array{label: string, granted: bool}>>
     */
    public static function describeForPermissions(iterable $permissionSlugs): array
    {
        $granted = [];
        foreach ($permissionSlugs as $item) {
            $slug = is_string($item) ? $item : (string) ($item->slug ?? '');
            if ($slug !== '') {
                $granted[$slug] = true;
            }
        }

        $out = [];
        foreach (self::permissionGroups() as $category => $items) {
            $out[$category] = [];
            foreach ($items as $item) {
                $slug = $item['permission'];
                $has = isset($granted[$slug]);
                if (! $has) {
                    foreach (self::coarseParents()[$slug] ?? [] as $parent) {
                        if (isset($granted[$parent])) {
                            $has = true;
                            break;
                        }
                    }
                }
                $out[$category][] = [
                    'label' => $item['label'],
                    'granted' => $has,
                ];
            }
        }

        return $out;
    }

    /**
     * Garante que todos os slugs administrativos existem na tabela permissions.
     *
     * @return array<string, Permission>
     */
    public static function ensurePermissionRecords(): array
    {
        $defs = [
            'users.create' => ['Criar vendedor', 'users'],
            'users.update' => ['Editar vendedor', 'users'],
            'users.deactivate' => ['Inativar vendedor', 'users'],
            'users.reset_password' => ['Resetar senha', 'users'],
            'users.manage_permissions' => ['Gerenciar permissões', 'users'],
            'dashboard.team' => ['Dashboard gerencial', 'dashboard'],
            'properties.create' => ['Adicionar ponto', 'properties'],
            'properties.update' => ['Editar ponto', 'properties'],
            'properties.adjust' => ['Ajustar localização', 'properties'],
            'properties.delete' => ['Remover ponto', 'properties'],
            'properties.notes' => ['Editar observações', 'properties'],
            'properties.address' => ['Editar endereço', 'properties'],
            'maps.team_view' => ['Ver pontos da equipe', 'maps'],
            'visits.return' => ['Registrar retorno', 'visits'],
            'visits.contract' => ['Registrar contrato', 'visits'],
            'visits.status' => ['Alterar status de visita', 'visits'],
            'reports.export' => ['Exportar resultados', 'reports'],
            'campaigns.create' => ['Criar campanhas', 'campaigns'],
            'campaigns.update' => ['Editar campanhas', 'campaigns'],
            'campaigns.close' => ['Encerrar campanhas', 'campaigns'],
        ];

        $map = [];
        foreach ($defs as $slug => [$name, $module]) {
            $map[$slug] = Permission::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'module' => $module]
            );
        }

        foreach (self::commercialPermissionSlugs() as $slug) {
            if (! isset($map[$slug])) {
                $existing = Permission::query()->where('slug', $slug)->first();
                if ($existing) {
                    $map[$slug] = $existing;
                }
            }
        }

        return $map;
    }
}
