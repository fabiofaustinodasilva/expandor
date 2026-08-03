<?php

use Database\Seeders\EnsureCustomerPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new EnsureCustomerPermissionsSeeder)->run();
    }

    public function down(): void
    {
        // Intentionally empty: do not remove customer permissions on rollback.
    }
};
