<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alinha o catálogo SaaS aos planos comerciais do site sem apagar assinaturas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('plans', 'max_sellers')) {
                $table->unsignedInteger('max_sellers')->nullable()->after('max_users');
            }
            if (! Schema::hasColumn('plans', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('is_featured');
            }
            if (! Schema::hasColumn('plans', 'is_legacy')) {
                $table->boolean('is_legacy')->default(false)->after('is_public');
            }
            if (! Schema::hasColumn('plans', 'allows_checkout')) {
                $table->boolean('allows_checkout')->default(false)->after('is_legacy');
            }
        });

        if (! Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')->where('slug', 'free')->update([
            'is_public' => false,
            'is_legacy' => true,
            'allows_checkout' => false,
            'is_featured' => false,
            'display_order' => 110,
        ]);

        DB::table('plans')->where('slug', 'professional')->update([
            'is_public' => false,
            'is_legacy' => true,
            'allows_checkout' => false,
            'is_featured' => false,
            'display_order' => 120,
        ]);

        $enterprise = DB::table('plans')->where('slug', 'enterprise')->first();
        if ($enterprise !== null) {
            $price = (float) ($enterprise->price ?? 0);
            if ($price > 0) {
                DB::table('plans')->where('id', $enterprise->id)->update([
                    'slug' => 'enterprise-legacy',
                    'name' => 'Enterprise (legado)',
                    'is_public' => false,
                    'is_legacy' => true,
                    'allows_checkout' => false,
                    'is_featured' => false,
                    'display_order' => 130,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('plans')) {
            $legacy = DB::table('plans')->where('slug', 'enterprise-legacy')->first();
            $current = DB::table('plans')->where('slug', 'enterprise')->first();
            if ($legacy !== null && $current === null) {
                DB::table('plans')->where('id', $legacy->id)->update([
                    'slug' => 'enterprise',
                    'name' => 'Enterprise',
                    'is_legacy' => false,
                ]);
            }
        }

        Schema::table('plans', function (Blueprint $table): void {
            foreach (['allows_checkout', 'is_legacy', 'is_public', 'max_sellers'] as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
