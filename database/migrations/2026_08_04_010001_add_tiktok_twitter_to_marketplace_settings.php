<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketplace_settings', 'tiktok_enabled')) {
                $table->boolean('tiktok_enabled')->default(false)->after('linkedin_url');
            }
            if (! Schema::hasColumn('marketplace_settings', 'tiktok_url')) {
                $table->string('tiktok_url')->nullable()->after('tiktok_enabled');
            }
            if (! Schema::hasColumn('marketplace_settings', 'twitter_enabled')) {
                $table->boolean('twitter_enabled')->default(false)->after('tiktok_url');
            }
            if (! Schema::hasColumn('marketplace_settings', 'twitter_url')) {
                $table->string('twitter_url')->nullable()->after('twitter_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_settings', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('marketplace_settings', 'tiktok_enabled') ? 'tiktok_enabled' : null,
                Schema::hasColumn('marketplace_settings', 'tiktok_url') ? 'tiktok_url' : null,
                Schema::hasColumn('marketplace_settings', 'twitter_enabled') ? 'twitter_enabled' : null,
                Schema::hasColumn('marketplace_settings', 'twitter_url') ? 'twitter_url' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
