<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 7.1 — exibição marketplace dos planos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('plans', 'display_order')) {
                $table->unsignedInteger('display_order')->default(100)->after('status');
            }
            if (! Schema::hasColumn('plans', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('display_order');
            }
            if (! Schema::hasColumn('plans', 'max_visits')) {
                $table->unsignedInteger('max_visits')->nullable()->after('max_storage_mb');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            foreach (['display_order', 'is_featured', 'max_visits'] as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
