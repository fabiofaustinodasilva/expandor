<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('contracted_amount', 12, 2)->nullable()->after('plan_id');
            $table->unsignedTinyInteger('billing_day')->nullable()->after('billing_cycle');
            $table->boolean('has_commercial_exception')->default(false)->after('billing_day');
            $table->text('commercial_exception_reason')->nullable()->after('has_commercial_exception');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'contracted_amount',
                'billing_day',
                'has_commercial_exception',
                'commercial_exception_reason',
            ]);
        });
    }
};
