<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_commissions', function (Blueprint $table) {
            $table->dropForeign(['visit_id']);
        });

        Schema::table('sales_commissions', function (Blueprint $table) {
            $table->dropUnique(['visit_id']);
        });

        Schema::table('sales_commissions', function (Blueprint $table) {
            $table->foreign('visit_id')
                ->references('id')
                ->on('visits')
                ->cascadeOnDelete();

            $table->foreignId('sale_item_id')
                ->nullable()
                ->after('visit_id')
                ->constrained('sale_items')
                ->nullOnDelete();

            $table->unique('sale_item_id');
            $table->index(['visit_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_commissions', function (Blueprint $table) {
            $table->dropUnique(['sale_item_id']);
            $table->dropConstrainedForeignId('sale_item_id');
            $table->dropIndex(['visit_id', 'product_id']);
            $table->dropForeign(['visit_id']);
        });

        Schema::table('sales_commissions', function (Blueprint $table) {
            $table->foreign('visit_id')
                ->references('id')
                ->on('visits')
                ->cascadeOnDelete();
            $table->unique('visit_id');
        });
    }
};
