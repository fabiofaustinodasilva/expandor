<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('default_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_flag_id')->constrained('feature_flags')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'feature_flag_id']);
            $table->index(['company_id', 'enabled']);
        });

        Schema::create('impersonation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['platform_admin_id', 'ended_at']);
            $table->index(['target_company_id', 'started_at']);
        });

        Schema::create('company_health_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('risk_level', 20)->default('medium');
            $table->json('factors')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique('company_id');
            $table->index(['score', 'calculated_at']);
            $table->index('risk_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_health_scores');
        Schema::dropIfExists('impersonation_sessions');
        Schema::dropIfExists('company_feature_flags');
        Schema::dropIfExists('feature_flags');
    }
};
