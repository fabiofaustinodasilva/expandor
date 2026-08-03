<?php

use App\Domains\Company\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('onboarding_status', 32)
                ->default(Company::ONBOARDING_PENDING)
                ->after('status');
            $table->unsignedTinyInteger('onboarding_step')
                ->default(1)
                ->after('onboarding_status');
            $table->timestamp('onboarding_completed_at')
                ->nullable()
                ->after('onboarding_step');
        });

        // Empresas já existentes não entram no funil premium.
        DB::table('companies')->update([
            'onboarding_status' => Company::ONBOARDING_COMPLETED,
            'onboarding_step' => 5,
            'onboarding_completed_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn([
                'onboarding_status',
                'onboarding_step',
                'onboarding_completed_at',
            ]);
        });
    }
};
