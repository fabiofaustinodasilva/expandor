<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Idempotent: ensures Sprint 5.3 customers CRM permissions exist.
 */
class EnsureCustomerPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['name' => 'View customers CRM', 'slug' => 'customers.view', 'module' => 'customers'],
            ['name' => 'Manage customers CRM', 'slug' => 'customers.manage', 'module' => 'customers'],
        ];

        foreach ($definitions as $definition) {
            Permission::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                $definition
            );
        }

        $viewId = Permission::query()->where('slug', 'customers.view')->value('id');
        $manageId = Permission::query()->where('slug', 'customers.manage')->value('id');

        if (! $viewId || ! $manageId) {
            return;
        }

        $roleGrants = [
            Role::ADMINISTRATOR => [$viewId, $manageId],
            Role::MANAGER => [$viewId, $manageId],
            Role::SUPERVISOR => [$viewId, $manageId],
            Role::SELLER => [$viewId],
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
