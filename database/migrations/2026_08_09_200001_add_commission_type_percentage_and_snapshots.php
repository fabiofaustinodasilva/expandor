<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 8.2.23 — Comissões 2.0 (aditivo).
 * Sem recalcular sales_commissions históricas.
 * Legado: products.commission_amount permanece; type default fixed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'commission_type')) {
                $table->string('commission_type', 20)->default('fixed')->after('price');
            }
            if (! Schema::hasColumn('products', 'commission_percentage')) {
                $table->decimal('commission_percentage', 8, 4)->nullable()->after('commission_amount');
            }
        });

        // Semantic migration: existing amounts stay; type = fixed.
        if (Schema::hasColumn('products', 'commission_type')) {
            DB::table('products')->whereNull('commission_type')->update(['commission_type' => 'fixed']);
            DB::table('products')->where('commission_type', '')->update(['commission_type' => 'fixed']);
        }

        Schema::table('sale_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('sale_items', 'commission_type')) {
                $table->string('commission_type', 20)->nullable()->after('commission_amount');
            }
            if (! Schema::hasColumn('sale_items', 'commission_rate')) {
                $table->decimal('commission_rate', 12, 4)->nullable()->after('commission_type');
            }
            if (! Schema::hasColumn('sale_items', 'commission_base')) {
                $table->decimal('commission_base', 12, 2)->nullable()->after('commission_rate');
            }
        });

        Schema::table('sales_commissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('sales_commissions', 'commission_type')) {
                $table->string('commission_type', 20)->nullable()->after('commission_amount');
            }
            if (! Schema::hasColumn('sales_commissions', 'commission_rate')) {
                $table->decimal('commission_rate', 12, 4)->nullable()->after('commission_type');
            }
            if (! Schema::hasColumn('sales_commissions', 'commission_base')) {
                $table->decimal('commission_base', 12, 2)->nullable()->after('commission_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_commissions', function (Blueprint $table): void {
            $cols = array_values(array_filter([
                Schema::hasColumn('sales_commissions', 'commission_base') ? 'commission_base' : null,
                Schema::hasColumn('sales_commissions', 'commission_rate') ? 'commission_rate' : null,
                Schema::hasColumn('sales_commissions', 'commission_type') ? 'commission_type' : null,
            ]));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $cols = array_values(array_filter([
                Schema::hasColumn('sale_items', 'commission_base') ? 'commission_base' : null,
                Schema::hasColumn('sale_items', 'commission_rate') ? 'commission_rate' : null,
                Schema::hasColumn('sale_items', 'commission_type') ? 'commission_type' : null,
            ]));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            $cols = array_values(array_filter([
                Schema::hasColumn('products', 'commission_percentage') ? 'commission_percentage' : null,
                Schema::hasColumn('products', 'commission_type') ? 'commission_type' : null,
            ]));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
