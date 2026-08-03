<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('document', 40)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('gateway', 40)->index();
            $table->string('gateway_customer_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_customer_id']);
            $table->index(['company_id', 'email']);
        });

        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->string('gateway', 40)->index();
            $table->string('gateway_session_id')->nullable()->index();
            $table->string('checkout_url')->nullable();
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('buyer_document', 40)->nullable();
            $table->string('buyer_phone', 40)->nullable();
            $table->string('company_name');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BRL');
            $table->string('billing_cycle', 20)->default('monthly');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'gateway']);
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('gateway', 40);
            $table->string('gateway_payment_method_id')->nullable()->index();
            $table->string('brand', 40)->nullable();
            $table->string('last_four', 4)->nullable();
            $table->unsignedTinyInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status', 30)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->nullable()->index();
            $table->string('status', 30)->default('open')->index();
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('gateway', 40)->nullable();
            $table->string('gateway_invoice_id')->nullable()->index();
            $table->string('pdf_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checkout_session_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BRL');
            $table->string('status', 30)->default('pending')->index();
            $table->string('method', 30)->nullable();
            $table->string('gateway', 40)->index();
            $table->string('gateway_payment_id')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_payment_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 40);
            $table->string('event_id')->nullable();
            $table->string('event_type')->nullable()->index();
            $table->json('payload');
            $table->string('status', 30)->default('received')->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
            $table->index(['gateway', 'status']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('gateway', 40)->nullable()->after('trial_ends_at');
            $table->string('gateway_subscription_id')->nullable()->after('gateway')->index();
            $table->string('billing_cycle', 20)->nullable()->after('gateway_subscription_id');
            $table->timestamp('next_billing_at')->nullable()->after('billing_cycle');
            $table->timestamp('cancelled_at')->nullable()->after('next_billing_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'gateway_subscription_id',
                'billing_cycle',
                'next_billing_at',
                'cancelled_at',
            ]);
        });

        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('checkout_sessions');
        Schema::dropIfExists('customers');
    }
};
