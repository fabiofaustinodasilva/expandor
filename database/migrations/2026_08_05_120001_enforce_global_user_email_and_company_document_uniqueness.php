<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 8.1.9 — e-mail de login único globalmente; documento de empresa único quando informado.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Normaliza e-mails para lowercase antes do índice único.
        if (Schema::hasTable('users')) {
            DB::table('users')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $normalized = strtolower(trim((string) $row->email));
                    if ($normalized !== (string) $row->email) {
                        DB::table('users')->where('id', $row->id)->update(['email' => $normalized]);
                    }
                }
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'email']);
            });

            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
            });
        }

        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                // Múltiplos NULL continuam permitidos; valores preenchidos ficam únicos.
                $table->unique('document');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropUnique(['document']);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });

            Schema::table('users', function (Blueprint $table) {
                $table->unique(['company_id', 'email']);
            });
        }
    }
};
