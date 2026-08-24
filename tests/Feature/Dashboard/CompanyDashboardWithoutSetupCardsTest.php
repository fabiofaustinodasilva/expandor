<?php

namespace Tests\Feature\Dashboard;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class CompanyDashboardWithoutSetupCardsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_tenant_dashboard_hides_setup_and_activation_cards(): void
    {
        $company = $this->makeCompanyWithPlan('Dash No Setup');
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_PENDING,
            'onboarding_step' => 1,
            'onboarding_completed_at' => null,
        ])->save();

        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'dash.nosetup@expandor.test',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Continuar setup', false);
        $response->assertDontSee('Progresso de ativação', false);
        $response->assertDontSee('data-activation-guidance="1"', false);
        $response->assertDontSee('data-saas-activation-card="1"', false);
        $response->assertDontSee('Configure sua conta', false);
        $response->assertDontSee('/onboarding', false);
        $response->assertDontSee(route('setup.show', absolute: false), false);
        $response->assertSee('Abrir mapa', false);
        $html = $response->getContent();
        $this->assertStringNotContainsString('>Setup</', $html);
    }

    public function test_map_and_dashboard_remain_accessible(): void
    {
        $company = $this->makeCompanyWithPlan('Dash Access');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'dash.access@expandor.test',
        ]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('map.index'))->assertOk();
    }

    public function test_platform_owner_still_sees_activation_score(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Platform Activation Keep');
        $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'keep.act@expandor.test']);

        $this->actingAs($owner)
            ->get(route('platform.companies.show', $company))
            ->assertOk()
            ->assertSee('data-activation-score="1"', false)
            ->assertSee('Activation Score', false);
    }
}
