<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('document');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->string('reference', 255)->nullable()->after('neighborhood');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedTinyInteger('due_day')->nullable()->after('negotiated_amount');
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn('birth_date');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('reference');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('due_day');
        });
    }
};
