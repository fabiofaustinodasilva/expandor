<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 8.2.11 — presentation metadata for field sales catalog.
 * Extends existing products row (no parallel Media model; video as URL like marketplace).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'category')) {
                $table->string('category', 80)->nullable()->after('name');
            }
            if (! Schema::hasColumn('products', 'benefits')) {
                $table->json('benefits')->nullable()->after('description');
            }
            if (! Schema::hasColumn('products', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('status');
            }
            if (! Schema::hasColumn('products', 'video_url')) {
                $table->string('video_url', 500)->nullable()->after('image_thumb');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            foreach (['category', 'benefits', 'sort_order', 'video_url'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
