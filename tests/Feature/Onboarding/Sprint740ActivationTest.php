<?php

namespace Tests\Feature\Onboarding;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint740ActivationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_legacy_company_remains_completed(): void
    {
        $company = $this->makeCompanyWithPlan('Legacy 740');

        $this->assertSame(Company::ONBOARDING_COMPLETED, $company->onboarding_status);
        $this->assertFalse($company->needsSaasOnboarding());
    }

    public function test_new_company_pending_status_is_not_blocking(): void
    {
        $company = Company::factory()->pendingOnboarding()->create(['name' => 'Pending 740']);

        $this->assertSame(Company::ONBOARDING_PENDING, $company->onboarding_status);
        $this->assertSame(1, $company->onboarding_step);
        $this->assertFalse($company->needsSaasOnboarding());
    }

    public function test_pending_company_dashboard_has_no_mandatory_setup_banner(): void
    {
        $company = $this->makeCompanyWithPlan('Activation 740');
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_PENDING,
            'onboarding_step' => 1,
            'onboarding_completed_at' => null,
        ])->save();

        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin740@onboard.test']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-saas-onboarding-banner="1"', false)
            ->assertDontSee('data-saas-activation-card="1"', false);
    }

    public function test_complete_still_works_and_skip_advances(): void
    {
        $company = $this->makeCompanyWithPlan('Skip Deal Co');
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_IN_PROGRESS,
            'onboarding_step' => SaasOnboardingService::STEP_SALES_SETUP,
            'onboarding_completed_at' => null,
        ])->save();
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'skip740@onboard.test']);

        $this->actingAs($admin)
            ->post(route('onboarding.skip'), ['step' => 'deal'])
            ->assertRedirect(route('onboarding.branding'));

        $company->refresh();
        $this->assertSame(SaasOnboardingService::STEP_BRANDING, $company->onboarding_step);
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'onboarding.step_skipped')
                ->exists()
        );

        // GET da etapa agora redireciona (setup não obrigatório).
        $this->actingAs($admin)
            ->get(route('onboarding.branding'))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('onboarding.complete'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(Company::ONBOARDING_COMPLETED, $company->fresh()->onboarding_status);
    }
}
