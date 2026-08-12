<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 8.2.33 — metadata de device binding no token Sanctum.
 * Colunas aditivas; o package continua dono do restante da tabela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->string('device_id', 64)->nullable()->after('abilities');
            $table->string('device_name', 120)->nullable()->after('device_id');
            $table->string('platform', 32)->nullable()->after('device_name');
            $table->string('app_version', 32)->nullable()->after('platform');
            $table->unsignedInteger('session_version')->nullable()->after('app_version');

            $table->index(['tokenable_type', 'tokenable_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropIndex(['tokenable_type', 'tokenable_id', 'device_id']);
            $table->dropColumn([
                'device_id',
                'device_name',
                'platform',
                'app_version',
                'session_version',
            ]);
        });
    }
};
