<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketplace_settings', 'demo_video_url')) {
                $table->string('demo_video_url', 500)->nullable()->after('hero_video');
            }
            if (! Schema::hasColumn('marketplace_settings', 'og_image')) {
                $table->string('og_image')->nullable()->after('demo_video_url');
            }
            if (! Schema::hasColumn('marketplace_settings', 'conversion_content')) {
                $table->json('conversion_content')->nullable()->after('seo_keywords');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_settings', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('marketplace_settings', 'demo_video_url') ? 'demo_video_url' : null,
                Schema::hasColumn('marketplace_settings', 'og_image') ? 'og_image' : null,
                Schema::hasColumn('marketplace_settings', 'conversion_content') ? 'conversion_content' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
