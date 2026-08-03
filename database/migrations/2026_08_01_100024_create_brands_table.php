<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('system_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('login_image')->nullable();

            $table->json('colors')->nullable();
            $table->string('theme', 30)->nullable();
            $table->json('fonts')->nullable();

            $table->string('support_email')->nullable();
            $table->string('support_phone', 40)->nullable();
            $table->json('socials')->nullable();

            $table->string('custom_domain')->nullable();
            $table->longText('custom_css')->nullable();

            $table->timestamps();

            $table->unique('company_id');
            $table->unique('custom_domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
