<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('ibge_id')->unique();
            $table->string('uf', 2)->unique();
            $table->string('name', 64);
            $table->timestamps();
        });

        Schema::create('geo_municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_state_id')->constrained('geo_states')->cascadeOnDelete();
            $table->string('ibge_code', 10)->unique();
            $table->string('name');
            $table->timestamps();

            $table->index(['geo_state_id', 'name']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_municipalities');
        Schema::dropIfExists('geo_states');
    }
};
