<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_events', function (Blueprint $table): void {
            $table->string('session_id', 64)->nullable()->after('event');
            $table->foreignId('company_id')->nullable()->after('session_id')->constrained('companies')->nullOnDelete();
            $table->unsignedBigInteger('lead_id')->nullable()->after('company_id');
            $table->string('url', 500)->nullable()->after('lead_id');
            $table->string('referrer', 500)->nullable()->after('url');
            $table->string('utm_source', 120)->nullable()->after('referrer');
            $table->string('utm_medium', 120)->nullable()->after('utm_source');
            $table->string('utm_campaign', 180)->nullable()->after('utm_medium');
            $table->string('utm_term', 180)->nullable()->after('utm_campaign');
            $table->string('utm_content', 180)->nullable()->after('utm_term');
            $table->string('device', 40)->nullable()->after('utm_content');
            $table->index('session_id');
            $table->index(['utm_source', 'utm_campaign']);
            $table->index('lead_id');
        });

        Schema::create('marketplace_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('segment', 120)->nullable();
            $table->string('employees', 40)->nullable();
            $table->string('source', 120)->nullable();
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 180)->nullable();
            $table->string('utm_term', 180)->nullable();
            $table->string('utm_content', 180)->nullable();
            $table->string('status', 40)->default('new');
            $table->text('notes')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index('email');
            $table->index(['utm_source', 'utm_campaign']);
        });

        Schema::table('marketplace_events', function (Blueprint $table): void {
            $table->foreign('lead_id')->references('id')->on('marketplace_leads')->nullOnDelete();
        });

        Schema::create('marketplace_segment_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_video')->nullable();
            $table->json('features')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'slug']);
        });

        Schema::create('marketplace_cases', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->string('segment', 120)->nullable();
            $table->text('challenge')->nullable();
            $table->text('solution')->nullable();
            $table->text('result')->nullable();
            $table->string('image')->nullable();
            $table->string('video')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'order']);
        });

        Schema::create('marketplace_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('source', 120)->nullable();
            $table->string('medium', 120)->nullable();
            $table->string('campaign', 180)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'campaign']);
            $table->index(['source', 'medium']);
        });

        Schema::create('marketplace_trial_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('event', 80);
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'event']);
            $table->index(['event', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_trial_milestones');
        Schema::dropIfExists('marketplace_campaigns');
        Schema::dropIfExists('marketplace_cases');
        Schema::dropIfExists('marketplace_segment_pages');

        Schema::table('marketplace_events', function (Blueprint $table): void {
            $table->dropForeign(['lead_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn([
                'session_id',
                'company_id',
                'lead_id',
                'url',
                'referrer',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'device',
            ]);
        });

        Schema::dropIfExists('marketplace_leads');
    }
};
