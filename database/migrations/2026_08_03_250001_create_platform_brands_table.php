<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->default('Expandor');
            $table->string('logo')->nullable();
            $table->string('logo_small')->nullable();
            $table->string('favicon')->nullable();
            $table->json('colors')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_brands');
    }
};
