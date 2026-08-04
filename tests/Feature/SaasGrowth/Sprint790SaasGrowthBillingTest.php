<?php

namespace Tests\Feature\SaasGrowth;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\SaasGrowth\Enums\HealthClassification;
use App\Domains\SaasGrowth\Enums\TrialMilestone;
use App\Domains\SaasGrowth\Enums\UsageMetric;
use App\Domains\SaasGrowth\Models\TrialMilestoneRecord;
use App\Domains\SaasGrowth\Services\CompanyHealthScoreService;
use App\Domains\SaasGrowth\Services\LimitAlertService;
use App\Domains\SaasGrowth\Services\SaasIntelligenceDashboardService;
use App\Domains\SaasGrowth\Services\SaasUsageService;
use App\Domains\SaasGrowth\Services\TrialIntelligenceService;
use App\Domains\SaasGrowth\Services\UpgradeIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint790SaasGrowthBillingTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Cache::flush();
    }

    public function test_plan_limits_are_exposed_and_usage_percent_calculated(): void
    {
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();
        $plan->update([
            'users_limit' => 10,
            'customers_limit' => 100,
            'storage_limit' => 1000,
            'max_users' => 10,
            'max_properties' => 100,
            'max_storage_mb' => 1000,
            'active' => true,
        ]);

        $company = $this->makeCompanyWithPlan('Uso Limite Co', 'professional');
        $this->makeUser($company, 'administrator', ['email' => 'admin-limit@test.local']);

        // 8 users would be 80% of 10 — create 7 more (1 already)
        for ($i = 0; $i < 7; $i++) {
            $this->makeUser($company, 'seller', ['email' => "seller{$i}@limit.test"]);
        }

        $usage = app(SaasUsageService::class)->snapshot($company);
        $this->assertSame(10, $usage->limit(UsageMetric::Users->value));
        $this->assertSame(8, $usage->value(UsageMetric::Users->value));
        $this->assertEquals(80.0, $usage->percent(UsageMetric::Users->value));
    }

    public function test_limit_warning_at_80_and_reached_at_90(): void
    {
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();
        $plan->update([
            'users_limit' => 10,
            'max_users' => 10,
            'customers_limit' => 500,
            'max_properties' => 500,
        ]);

        $company = $this->makeCompanyWithPlan('Alerta 80', 'professional');
        for ($i = 0; $i < 8; $i++) {
            $this->makeUser($company, $i === 0 ? 'administrator' : 'seller', [
                'email' => "u80_{$i}@test.local",
            ]);
        }

        Cache::flush();
        $alerts = app(LimitAlertService::class)->evaluate($company);
        $this->assertTrue(collect($alerts)->contains(fn ($a) => $a['event'] === 'saas.limit_warning'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'saas.limit_warning']);

        for ($i = 8; $i < 9; $i++) {
            $this->makeUser($company, 'seller', ['email' => "u90_{$i}@test.local"]);
        }

        Cache::flush();
        $alerts90 = app(LimitAlertService::class)->evaluate($company);
        $this->assertTrue(collect($alerts90)->contains(fn ($a) => $a['event'] === 'saas.limit_reached'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'saas.limit_reached']);
    }

    public function test_trial_milestones_and_conversion(): void
    {
        $company = $this->makeCompanyWithPlan('Trial Miles', 'professional');
        $trials = app(TrialIntelligenceService::class);

        $trials->markStarted($company);
        $trials->complete($company, TrialMilestone::TeamCreated);
        $trials->complete($company, TrialMilestone::CustomerCreated);
        $trials->complete($company, TrialMilestone::DealCreated);
        $trials->markConverted($company);

        $this->assertDatabaseHas('trial_milestones', [
            'company_id' => $company->id,
            'milestone' => TrialMilestone::CompanyCreated->value,
        ]);
        $this->assertDatabaseHas('trial_milestones', [
            'company_id' => $company->id,
            'milestone' => TrialMilestone::Activated->value,
        ]);
        $this->assertSame(5, TrialMilestoneRecord::query()->where('company_id', $company->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'trial.started']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'trial.converted']);
    }

    public function test_health_score_classification(): void
    {
        $service = app(CompanyHealthScoreService::class);
        $this->assertSame(HealthClassification::Risk, $service->classificationForScore(20));
        $this->assertSame(HealthClassification::Attention, $service->classificationForScore(50));
        $this->assertSame(HealthClassification::Healthy, $service->classificationForScore(80));

        $company = $this->makeCompanyWithPlan('Health Co', 'professional');
        $this->makeUser($company, 'administrator', ['email' => 'health@test.local']);

        $row = $service->calculate($company);
        $this->assertNotNull($row->classification);
        $this->assertDatabaseHas('audit_logs', ['action' => 'health_score_calculated']);
    }

    public function test_upgrade_recommendation_and_dashboard_metrics(): void
    {
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();
        $plan->update([
            'customers_limit' => 500,
            'max_properties' => 500,
            'users_limit' => 100,
            'max_users' => 100,
        ]);

        $company = $this->makeCompanyWithPlan('Upgrade Co', 'professional');
        $this->makeUser($company, 'administrator', ['email' => 'upg@test.local']);

        // 450 customers of 500 = 90%
        Lead::query()->withoutGlobalScopes()->insert(
            collect(range(1, 450))->map(fn ($i) => [
                'company_id' => $company->id,
                'name' => "Lead {$i}",
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );

        Cache::flush();
        $recs = app(UpgradeIntelligenceService::class)->recommendations($company);
        $this->assertNotEmpty($recs);
        $this->assertDatabaseHas('audit_logs', ['action' => 'subscription.upgrade_recommended']);

        Cache::forget(SaasIntelligenceDashboardService::CACHE_KEY);
        $metrics = app(SaasIntelligenceDashboardService::class)->metrics();
        $this->assertGreaterThanOrEqual(1, $metrics->totalCompanies);
        $this->assertGreaterThanOrEqual(0, $metrics->estimatedMrr);
    }

    public function test_platform_saas_intelligence_page(): void
    {
        $owner = $this->makePlatformAdmin();
        $this->actingAs($owner)
            ->get(route('platform.saas.intelligence'))
            ->assertOk()
            ->assertSee('SaaS Intelligence');
    }
}
