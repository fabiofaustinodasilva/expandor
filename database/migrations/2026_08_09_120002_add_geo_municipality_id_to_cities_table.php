<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->foreignId('geo_municipality_id')
                ->nullable()
                ->after('ibge_code')
                ->constrained('geo_municipalities')
                ->nullOnDelete();

            $table->unique(['company_id', 'geo_municipality_id'], 'cities_company_geo_municipality_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropUnique('cities_company_geo_municipality_unique');
            $table->dropConstrainedForeignId('geo_municipality_id');
        });
    }
};
