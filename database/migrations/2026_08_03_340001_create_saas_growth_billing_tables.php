<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            $table->unsignedInteger('users_limit')->nullable()->after('max_users');
            $table->unsignedInteger('customers_limit')->nullable()->after('max_properties');
            $table->unsignedInteger('storage_limit')->nullable()->after('max_storage_mb');
            $table->boolean('active')->default(true)->after('status');
        });

        // Backfill from legacy columns without changing billing behavior.
        if (Schema::hasTable('plans')) {
            foreach (DB::table('plans')->orderBy('id')->get() as $plan) {
                DB::table('plans')->where('id', $plan->id)->update([
                    'users_limit' => $plan->max_users,
                    'customers_limit' => $plan->max_properties,
                    'storage_limit' => $plan->max_storage_mb,
                    'active' => ($plan->status ?? 'active') === 'active',
                ]);
            }
        }

        Schema::create('company_usage_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('metric', 60);
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['company_id', 'metric', 'recorded_at']);
            $table->unique(['company_id', 'metric', 'recorded_at']);
        });

        Schema::create('trial_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('milestone', 80);
            $table->timestamp('completed_at');
            $table->timestamps();
            $table->unique(['company_id', 'milestone']);
            $table->index(['milestone', 'completed_at']);
        });

        Schema::table('company_health_scores', function (Blueprint $table): void {
            $table->string('classification', 20)->nullable()->after('risk_level');
        });
    }

    public function down(): void
    {
        Schema::table('company_health_scores', function (Blueprint $table): void {
            $table->dropColumn('classification');
        });
        Schema::dropIfExists('trial_milestones');
        Schema::dropIfExists('company_usage_metrics');
        Schema::table('plans', function (Blueprint $table): void {
            $table->dropColumn(['users_limit', 'customers_limit', 'storage_limit', 'active']);
        });
    }
};
