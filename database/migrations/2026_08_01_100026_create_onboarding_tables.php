<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_steps', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->string('wizard_key')->nullable();
            $table->json('training_keywords')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('onboarding_step_id')->constrained('onboarding_steps')->cascadeOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedTinyInteger('percent')->default(0);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'onboarding_step_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('setup_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('onboarding_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('status', 30)->default('in_progress')->index();
            $table->unsignedTinyInteger('percent')->default(0);
            $table->boolean('demo_generated')->default(false);
            $table->string('tour_status', 30)->default('pending');
            $table->timestamp('tour_completed_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('finished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_runs');
        Schema::dropIfExists('products');
        Schema::dropIfExists('setup_templates');
        Schema::dropIfExists('onboarding_progress');
        Schema::dropIfExists('onboarding_steps');
    }
};
