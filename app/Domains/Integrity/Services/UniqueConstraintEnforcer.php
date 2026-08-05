<?php

namespace App\Domains\Integrity\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Garante UNIQUE users.email e companies.document após o reparo (idempotente).
 */
class UniqueConstraintEnforcer
{
    public function ensure(): void
    {
        if (Schema::hasTable('users') && ! $this->hasUnique('users', ['email'])) {
            // Remove unique composto legado se ainda existir.
            if ($this->hasUnique('users', ['company_id', 'email'])) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique(['company_id', 'email']);
                });
            }

            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
            });
        }

        if (Schema::hasTable('companies') && ! $this->hasUnique('companies', ['document'])) {
            Schema::table('companies', function (Blueprint $table) {
                $table->unique('document');
            });
        }
    }

    /**
     * @param  list<string>  $columns
     */
    protected function hasUnique(string $table, array $columns): bool
    {
        $indexes = Schema::getConnection()->getSchemaBuilder()->getIndexes($table);

        foreach ($indexes as $index) {
            if (($index['unique'] ?? false) && ($index['columns'] ?? []) === $columns) {
                return true;
            }
        }

        return false;
    }
}
