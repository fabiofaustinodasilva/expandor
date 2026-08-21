<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('contract_started_at')->nullable()->after('starts_at');
            $table->unsignedTinyInteger('minimum_term_months')->nullable()->after('contract_started_at');
            $table->timestamp('minimum_term_ends_at')->nullable()->after('minimum_term_months');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->string('suspension_reason', 60)->nullable()->after('suspended_at');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('subscription_id')->constrained('plans')->nullOnDelete();
            $table->string('payment_method', 30)->nullable()->after('gateway');
            $table->string('billing_period_key', 40)->nullable()->after('number');
            $table->unique(['subscription_id', 'billing_period_key'], 'invoices_subscription_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_subscription_period_unique');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['payment_method', 'billing_period_key']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['suspended_at', 'suspension_reason']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['contract_started_at', 'minimum_term_months', 'minimum_term_ends_at']);
        });
    }
};
