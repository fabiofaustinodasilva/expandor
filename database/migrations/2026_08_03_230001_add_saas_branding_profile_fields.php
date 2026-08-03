<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('whatsapp', 40)->nullable()->after('phone');
            $table->string('address', 500)->nullable()->after('whatsapp');
            $table->string('segment', 40)->nullable()->after('address');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('logo_mark')->nullable()->after('logo');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp', 40)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'address', 'segment']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('logo_mark');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('whatsapp');
        });
    }
};
