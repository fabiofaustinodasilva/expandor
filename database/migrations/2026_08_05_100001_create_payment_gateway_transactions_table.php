<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkout_session_id')->nullable()->constrained('checkout_sessions')->nullOnDelete();
            $table->foreignId('payment_record_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('gateway', 40)->index();
            $table->string('payment_method', 30)->nullable()->index();
            $table->string('payment_id', 80)->nullable()->index();
            $table->string('status', 30)->nullable()->index();
            $table->text('pix_qr_code')->nullable();
            $table->longText('pix_qr_code_base64')->nullable();
            $table->timestamp('pix_expiration_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_transactions');
    }
};
