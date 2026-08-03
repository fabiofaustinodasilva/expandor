<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('commission_amount', 12, 2)->default(0)->after('price');
            $table->boolean('stock_control')->default(false)->after('commission_amount');
            $table->unsignedInteger('stock_quantity')->default(0)->after('stock_control');
            $table->unsignedInteger('minimum_stock')->default(0)->after('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'commission_amount',
                'stock_control',
                'stock_quantity',
                'minimum_stock',
            ]);
        });
    }
};
