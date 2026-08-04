<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_lead_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained('marketplace_leads')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->boolean('visited_pricing')->default(false);
            $table->boolean('watched_video')->default(false);
            $table->boolean('clicked_whatsapp')->default(false);
            $table->boolean('used_roi_calculator')->default(false);
            $table->boolean('requested_demo')->default(false);
            $table->boolean('started_trial')->default(false);
            $table->string('temperature', 20)->default('cold');
            $table->timestamp('hot_detected_at')->nullable();
            $table->timestamps();
            $table->unique('lead_id');
            $table->index(['temperature', 'score']);
        });

        Schema::create('marketplace_sales_pipeline', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained('marketplace_leads')->cascadeOnDelete();
            $table->string('stage', 40)->default('new');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamps();
            $table->unique('lead_id');
            $table->index(['stage', 'updated_at']);
        });

        Schema::table('marketplace_campaigns', function (Blueprint $table): void {
            $table->decimal('investment', 12, 2)->default(0)->after('campaign');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_campaigns', function (Blueprint $table): void {
            $table->dropColumn('investment');
        });
        Schema::dropIfExists('marketplace_sales_pipeline');
        Schema::dropIfExists('marketplace_lead_scores');
    }
};
