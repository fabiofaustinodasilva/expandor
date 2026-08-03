<?php

use Database\Seeders\EnsureCommissionPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Idempotent data migration: ensure Sprint 5.0 commission permission
     * slugs exist and are attached to Admin/Manager/Supervisor/Seller.
     * Does not remove existing permissions or role grants.
     */
    public function up(): void
    {
        (new EnsureCommissionPermissionsSeeder)->run();
    }

    public function down(): void
    {
        // Intentionally empty: do not remove commission permissions on rollback.
    }
};
