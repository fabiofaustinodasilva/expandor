<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 7.0 — campos comerciais de planos (preço anual, trial, limites extras).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('plans', 'price_yearly')) {
                $table->decimal('price_yearly', 10, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('plans', 'trial_days')) {
                $table->unsignedSmallInteger('trial_days')->nullable()->after('price_yearly');
            }
            if (! Schema::hasColumn('plans', 'max_teams')) {
                $table->unsignedInteger('max_teams')->nullable()->after('max_campaigns');
            }
            if (! Schema::hasColumn('plans', 'max_products')) {
                $table->unsignedInteger('max_products')->nullable()->after('max_teams');
            }
            if (! Schema::hasColumn('plans', 'max_storage_mb')) {
                $table->unsignedInteger('max_storage_mb')->nullable()->after('max_products');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            foreach (['price_yearly', 'trial_days', 'max_teams', 'max_products', 'max_storage_mb'] as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
