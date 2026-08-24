<?php

namespace Tests\Feature\Onboarding;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SaasOnboardingSprint730Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_new_company_starts_onboarding_pending(): void
    {
        $company = Company::factory()->pendingOnboarding()->create([
            'name' => 'Nova SaaS Co',
        ]);

        $this->assertSame(Company::ONBOARDING_PENDING, $company->onboarding_status);
        $this->assertSame(1, $company->onboarding_step);
        $this->assertNull($company->onboarding_completed_at);
        // Setup SaaS não é mais bloqueante, mesmo com status pending legado.
        $this->assertFalse($company->needsSaasOnboarding());
    }

    public function test_first_login_skips_onboarding_setup(): void
    {
        $company = Company::factory()->pendingOnboarding()->create(['name' => 'Login Onboard']);
        $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin730@onboard.test',
            'password' => 'Password123!',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin730@onboard.test',
            'password' => 'Password123!',
        ]);

        $location = (string) $response->headers->get('Location');
        $this->assertStringNotContainsString('/onboarding', $location);
        $this->assertStringNotContainsString('/setup', $location);
        $response->assertRedirect();
    }

    public function test_onboarding_routes_redirect_without_loop(): void
    {
        $company = $this->makeCompanyWithPlan('Fluxo Completo');
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_PENDING,
            'onboarding_step' => 1,
            'onboarding_completed_at' => null,
        ])->save();

        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'fluxo@onboard.test']);

        $this->actingAs($admin)
            ->get(route('onboarding.index'))
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('onboarding.company'))
            ->assertRedirect();

        $location = (string) $this->actingAs($admin)->get(route('onboarding.index'))->headers->get('Location');
        $this->assertStringNotContainsString('/onboarding/', $location.'#');
    }

    public function test_complete_action_still_marks_company_completed(): void
    {
        $company = $this->makeCompanyWithPlan('Complete Action');
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_PENDING,
            'onboarding_step' => 1,
            'onboarding_completed_at' => null,
        ])->save();

        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'complete730@onboard.test']);

        $this->actingAs($admin)
            ->post(route('onboarding.complete'))
            ->assertRedirect(route('dashboard'));

        $company->refresh();
        $this->assertSame(Company::ONBOARDING_COMPLETED, $company->onboarding_status);
        $this->assertNotNull($company->onboarding_completed_at);
        $this->assertFalse($company->needsSaasOnboarding());
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'onboarding.completed')
                ->exists()
        );
    }

    public function test_legacy_company_does_not_redirect_to_onboarding(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Antiga');
        $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'legacy730@onboard.test',
            'password' => 'Password123!',
        ]);

        $this->assertSame(Company::ONBOARDING_COMPLETED, $company->onboarding_status);
        $this->assertFalse($company->needsSaasOnboarding());

        $response = $this->post(route('login'), [
            'email' => 'legacy730@onboard.test',
            'password' => 'Password123!',
        ]);

        $location = $response->headers->get('Location') ?? '';
        $this->assertStringNotContainsString('/onboarding', $location);
        $response->assertRedirect();
    }
}
