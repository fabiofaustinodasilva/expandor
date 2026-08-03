<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 5.5.1 — separa slogan do nome da plataforma/tenant.
 * Idempotente: pode rodar com segurança se a coluna já existir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_brands') && ! Schema::hasColumn('platform_brands', 'slogan')) {
            Schema::table('platform_brands', function (Blueprint $table): void {
                $table->string('slogan', 255)->nullable()->after('name');
            });
        }

        if (Schema::hasTable('brands') && ! Schema::hasColumn('brands', 'slogan')) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->string('slogan', 255)->nullable()->after('display_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_brands') && Schema::hasColumn('platform_brands', 'slogan')) {
            Schema::table('platform_brands', function (Blueprint $table): void {
                $table->dropColumn('slogan');
            });
        }

        if (Schema::hasTable('brands') && Schema::hasColumn('brands', 'slogan')) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->dropColumn('slogan');
            });
        }
    }
};
