<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 50);
            $table->unsignedBigInteger('value')->default(0);
            $table->string('period', 20);
            $table->timestamps();

            $table->unique(['company_id', 'metric', 'period']);
            $table->index(['company_id', 'period']);
            $table->index('metric');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
