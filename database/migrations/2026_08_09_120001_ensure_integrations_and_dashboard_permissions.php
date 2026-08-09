<?php

use Database\Seeders\EnsureIntegrationsPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

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
