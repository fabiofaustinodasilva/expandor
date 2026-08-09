<?php

use Database\Seeders\EnsureIntegrationsPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Hotfix: re-apply additive integrations.manage grants for Administrator/Manager.
 *
 * The earlier 2026_08_09_120001 migration is already recorded on some environments;
 * a subsequent incomplete RolePermissionSeeder sync (or missing pivot) can leave
 * integrations.view without integrations.manage. This migration is safe to re-run
 * the additive seeder (syncWithoutDetaching only).
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EnsureIntegrationsPermissionsSeeder)->run();
    }

    public function down(): void
    {
        // Intentionally empty: do not revoke permissions on rollback.
    }
};
