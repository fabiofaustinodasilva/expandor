<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 8.2.17 hotfix — presença independente de SESSION_DRIVER.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('last_login_at');
                $table->index(['company_id', 'last_seen_at'], 'users_company_last_seen_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_seen_at')) {
                $table->dropIndex('users_company_last_seen_at_index');
                $table->dropColumn('last_seen_at');
            }
        });
    }
};
