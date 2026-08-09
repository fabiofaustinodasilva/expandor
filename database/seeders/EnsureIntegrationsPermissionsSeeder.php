<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Hotfix 8.2.21.1 / 8.2.22 — grants aditivos (nunca sync destrutivo).
 *
 * Garante integrations.view + integrations.manage e reancora dashboard.view
 * nas roles de tenant, cobrindo ambientes onde:
 * - migration anterior já rodou e não reexecuta;
 * - RolePermissionSeeder sync incompleto removeu o pivot;
 * - Administrator vê a Central (view) mas cai em "Somente visualização" (sem manage).
 *
 * Seguro chamar mais de uma vez: syncWithoutDetaching.
 */
class EnsureIntegrationsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['name' => 'View integrations', 'slug' => 'integrations.view', 'module' => 'integrations'],
            ['name' => 'Manage integrations', 'slug' => 'integrations.manage', 'module' => 'integrations'],
            ['name' => 'View dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],
        ];

        foreach ($definitions as $definition) {
            Permission::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                $definition
            );
        }

        $viewIntegrations = Permission::query()->where('slug', 'integrations.view')->value('id');
        $manageIntegrations = Permission::query()->where('slug', 'integrations.manage')->value('id');
        $dashboardView = Permission::query()->where('slug', 'dashboard.view')->value('id');

        if (! $viewIntegrations || ! $manageIntegrations || ! $dashboardView) {
            throw new \RuntimeException('EnsureIntegrationsPermissionsSeeder: permission rows missing.');
        }

        $roleGrants = [
            Role::ADMINISTRATOR => [$viewIntegrations, $manageIntegrations, $dashboardView],
            Role::MANAGER => [$viewIntegrations, $manageIntegrations, $dashboardView],
            Role::SUPERVISOR => [$dashboardView],
            Role::SELLER => [$dashboardView],
            Role::VIEWER => [$dashboardView],
        ];

        foreach ($roleGrants as $roleSlug => $permissionIds) {
            $role = Role::query()->where('slug', $roleSlug)->first();
            if (! $role) {
                continue;
            }

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
