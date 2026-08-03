<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Idempotent: ensures Sprint 5.0 commission permissions exist and are
 * attached to the expected roles without removing any other grants.
 */
class EnsureCommissionPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['name' => 'Manage commissions and products', 'slug' => 'commissions.manage', 'module' => 'commissions'],
            ['name' => 'View own commissions', 'slug' => 'commissions.view_self', 'module' => 'commissions'],
        ];

        foreach ($definitions as $definition) {
            Permission::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                $definition
            );
        }

        $manageId = Permission::query()->where('slug', 'commissions.manage')->value('id');
        $viewSelfId = Permission::query()->where('slug', 'commissions.view_self')->value('id');

        if (! $manageId || ! $viewSelfId) {
            return;
        }

        $roleGrants = [
            Role::ADMINISTRATOR => [$manageId, $viewSelfId],
            Role::MANAGER => [$manageId, $viewSelfId],
            Role::SUPERVISOR => [$manageId, $viewSelfId],
            Role::SELLER => [$viewSelfId],
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
