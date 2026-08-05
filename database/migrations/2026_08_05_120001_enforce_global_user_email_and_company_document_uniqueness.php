<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 8.1.9 — e-mail de login único globalmente; documento de empresa único quando informado.
 *
 * Sprint 8.1.9.2 — duplicatas históricas NÃO são limpas aqui via SQL.
 * Execute: php artisan integrity:repair --execute
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $normalized = strtolower(trim((string) $row->email));
                    if ($normalized !== (string) $row->email) {
                        DB::table('users')->where('id', $row->id)->update(['email' => $normalized]);
                    }
                }
            });

            $duplicateEmailGroups = DB::table('users')
                ->selectRaw('LOWER(email) as email_key, COUNT(*) as total')
                ->groupBy('email_key')
                ->having('total', '>', 1)
                ->get();

            if ($duplicateEmailGroups->isNotEmpty()) {
                throw new RuntimeException(
                    'Duplicated users.email detected. Run: php artisan integrity:repair --execute'
                );
            }

            $indexes = Schema::getConnection()->getSchemaBuilder()->getIndexes('users');
            $hasCompanyEmailUnique = false;
            $hasEmailUnique = false;

            foreach ($indexes as $index) {
                $cols = $index['columns'] ?? [];
                if ($cols === ['company_id', 'email']) {
                    $hasCompanyEmailUnique = true;
                }
                if (($index['unique'] ?? false) && $cols === ['email']) {
                    $hasEmailUnique = true;
                }
            }

            if ($hasCompanyEmailUnique) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique(['company_id', 'email']);
                });
            }

            if (! $hasEmailUnique) {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique('email');
                });
            }
        }

        if (Schema::hasTable('companies')) {
            $companies = DB::table('companies')
                ->whereNotNull('document')
                ->where('document', '!=', '')
                ->get(['id', 'document']);

            $seen = [];
            foreach ($companies as $company) {
                $key = preg_replace('/\D+/', '', (string) $company->document) ?: (string) $company->document;
                if (isset($seen[$key])) {
                    throw new RuntimeException(
                        'Duplicated companies.document detected. Run: php artisan integrity:repair --execute'
                    );
                }
                $seen[$key] = true;
            }

            $indexes = Schema::getConnection()->getSchemaBuilder()->getIndexes('companies');
            $hasDocumentUnique = false;
            foreach ($indexes as $index) {
                if (($index['unique'] ?? false) && ($index['columns'] ?? []) === ['document']) {
                    $hasDocumentUnique = true;
                    break;
                }
            }

            if (! $hasDocumentUnique) {
                Schema::table('companies', function (Blueprint $table) {
                    $table->unique('document');
                });
            }
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
