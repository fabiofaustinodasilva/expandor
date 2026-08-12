<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 8.2.33 — metadata de device binding no token Sanctum.
 * Hotfix: índice nomeado (MySQL identifier <= 64). Idempotente se o ALTER
 * anterior criou colunas e falhou no CREATE INDEX.
 */
return new class extends Migration
{
    public const INDEX = 'pat_tokenable_device_idx';

    public function up(): void
    {
        $table = 'personal_access_tokens';

        if (! Schema::hasTable($table)) {
            return;
        }

        $this->addColumnIfMissing($table, 'device_id', function (Blueprint $blueprint): void {
            $blueprint->string('device_id', 64)->nullable()->after('abilities');
        });
        $this->addColumnIfMissing($table, 'device_name', function (Blueprint $blueprint): void {
            $blueprint->string('device_name', 120)->nullable()->after('device_id');
        });
        $this->addColumnIfMissing($table, 'platform', function (Blueprint $blueprint): void {
            $blueprint->string('platform', 32)->nullable()->after('device_name');
        });
        $this->addColumnIfMissing($table, 'app_version', function (Blueprint $blueprint): void {
            $blueprint->string('app_version', 32)->nullable()->after('platform');
        });
        $this->addColumnIfMissing($table, 'session_version', function (Blueprint $blueprint): void {
            $blueprint->unsignedInteger('session_version')->nullable()->after('app_version');
        });

        if (! Schema::hasIndex($table, self::INDEX)) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->index(
                    ['tokenable_type', 'tokenable_id', 'device_id'],
                    self::INDEX,
                );
            });
        }
    }

    public function down(): void
    {
        $table = 'personal_access_tokens';

        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasIndex($table, self::INDEX)) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropIndex(self::INDEX);
            });
        }

        $drop = [];
        foreach (['device_id', 'device_name', 'platform', 'app_version', 'session_version'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $drop[] = $column;
            }
        }

        if ($drop !== []) {
            Schema::table($table, function (Blueprint $blueprint) use ($drop): void {
                $blueprint->dropColumn($drop);
            });
        }
    }

    private function addColumnIfMissing(string $table, string $column, callable $define): void
    {
        if (Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, $define);
    }
};
