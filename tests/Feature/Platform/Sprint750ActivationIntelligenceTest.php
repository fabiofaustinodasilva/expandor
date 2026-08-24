<?php

namespace Tests\Feature\Platform;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\CRM\Models\Lead;
use App\Domains\Onboarding\Events\OnboardingCompleted;
use App\Domains\Onboarding\Events\OnboardingCustomerCreated;
use App\Domains\Onboarding\Events\OnboardingStarted;
use App\Domains\Platform\Enums\ActivationHealthStatus;
use App\Domains\Platform\Services\ActivationIntelligenceService;
use App\Domains\Platform\Activation\Services\ActivationOutreachService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint750ActivationIntelligenceTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_legacy_company_keeps_working_and_has_score(): void
    {
        $company = $this->makeCompanyWithPlan('Legacy Score Co');
        $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'legacy750@test.com']);

        $this->assertSame(Company::ONBOARDING_COMPLETED, $company->onboarding_status);

        $snapshot = app(ActivationIntelligenceService::class)->snapshot($company);
        $this->assertGreaterThanOrEqual(0, $snapshot->score);
        $this->assertLessThanOrEqual(100, $snapshot->score);
        $this->assertInstanceOf(ActivationHealthStatus::class, $snapshot->status);
    }

    public function test_activation_score_increases_with_usage(): void
    {
        $company = $this->makeCompanyWithPlan('Score Usage Co');
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_PENDING,
            'onboarding_step' => 1,
            'onboarding_completed_at' => null,
        ])->save();
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'score750@test.com',
            'last_login_at' => now(),
        ]);

        $before = app(ActivationIntelligenceService::class)->scoreFor($company);

        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_COMPLETED,
            'onboarding_step' => 7,
            'onboarding_completed_at' => now(),
        ])->save();

        Lead::query()->create([
            'company_id' => $company->id,
            'name' => 'Cliente Score',
            'email' => 'lead750@test.com',
            'source' => 'manual',
            'status' => 'new',
            'assigned_to' => $admin->id,
        ]);

        $this->makeUser($company, Role::SELLER, ['email' => 'seller750@test.com']);

        $after = app(ActivationIntelligenceService::class)->scoreFor($company->fresh());
        $this->assertGreaterThan($before, $after);
    }

    public function test_activation_events_are_recorded_from_onboarding(): void
    {
        $company = $this->makeCompanyWithPlan('Events Co');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'events750@test.com']);

        OnboardingStarted::dispatch($company, $admin);
        OnboardingCustomerCreated::dispatch($company, $admin, ['lead_id' => 1]);
        OnboardingCompleted::dispatch($company, $admin);

        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'activation.started')
                ->exists()
        );
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'activation.first_customer')
                ->exists()
        );
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'activation.completed')
                ->exists()
        );
    }

    public function test_saas_health_dashboard_loads_metrics_and_alerts(): void
    {
        $owner = $this->makePlatformAdmin();
        $stuck = $this->makeCompanyWithPlan('Stuck Co');
        $stuck->forceFill([
            'onboarding_status' => Company::ONBOARDING_IN_PROGRESS,
            'onboarding_step' => 4,
            'onboarding_completed_at' => null,
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(10),
        ])->save();
        $this->makeUser($stuck, Role::ADMINISTRATOR, [
            'email' => 'stuck750@test.com',
            'last_login_at' => now()->subDays(12),
        ]);

        $this->actingAs($owner)
            ->get(route('platform.activation.index'))
            ->assertOk()
            ->assertSee('data-saas-health="1"', false)
            ->assertSee('Ativação', false)
            ->assertSee('Onboarding parado', false)
            ->assertSee('data-saas-cs-alerts="1"', false);

        $health = app(ActivationIntelligenceService::class)->healthDashboard();
        $this->assertGreaterThanOrEqual(1, $health->registeredCompanies);
        $this->assertIsFloat($health->activationRate);
    }

    public function test_company_show_includes_activation_timeline(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Show Activation Co');
        $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'show750@test.com']);

        $this->actingAs($owner)
            ->get(route('platform.companies.show', $company))
            ->assertOk()
            ->assertSee('data-activation-score="1"', false)
            ->assertSee('data-activation-timeline="1"', false)
            ->assertSee('Activation Score', false);
    }

    public function test_outreach_channels_are_registered(): void
    {
        $channels = app(ActivationOutreachService::class)->availableChannels();
        $this->assertContains('email', $channels);
        $this->assertContains('whatsapp', $channels);
        $this->assertContains('internal', $channels);
    }

    public function test_client_dashboard_does_not_show_activation_guidance(): void
    {
        $company = $this->makeCompanyWithPlan('Guidance Co');
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_IN_PROGRESS,
            'onboarding_step' => 3,
            'onboarding_completed_at' => null,
        ])->save();
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'guidance750@test.com']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-activation-guidance="1"', false)
            ->assertDontSee('Progresso de ativação', false)
            ->assertDontSee('Continuar setup', false);
    }
}
