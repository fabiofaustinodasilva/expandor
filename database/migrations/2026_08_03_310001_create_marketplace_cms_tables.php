<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_video')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('primary_color', 20)->default('#3B82F6');
            $table->string('secondary_color', 20)->default('#0F172A');
            $table->string('background_color', 20)->default('#0B1220');
            $table->string('button_color', 20)->default('#F59E0B');
            $table->boolean('whatsapp_enabled')->default(false);
            $table->string('whatsapp_number', 40)->nullable();
            $table->string('whatsapp_message')->nullable();
            $table->boolean('instagram_enabled')->default(false);
            $table->string('instagram_url')->nullable();
            $table->boolean('facebook_enabled')->default(false);
            $table->string('facebook_url')->nullable();
            $table->boolean('youtube_enabled')->default(false);
            $table->string('youtube_url')->nullable();
            $table->boolean('linkedin_enabled')->default(false);
            $table->string('linkedin_url')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->timestamps();
        });

        Schema::create('marketplace_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 40);
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('video')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'order']);
            $table->index('type');
        });

        Schema::create('marketplace_testimonials', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('avatar')->nullable();
            $table->text('text');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('marketplace_faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('marketplace_media', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20); // image|video
            $table->string('title')->nullable();
            $table->string('caption')->nullable();
            $table->string('path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('thumbnail')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('marketplace_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event', 80);
            $table->string('ip_hash', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('origin')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_events');
        Schema::dropIfExists('marketplace_media');
        Schema::dropIfExists('marketplace_faqs');
        Schema::dropIfExists('marketplace_testimonials');
        Schema::dropIfExists('marketplace_sections');
        Schema::dropIfExists('marketplace_settings');
    }
};
