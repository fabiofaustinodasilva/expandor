<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint826MobileResponsiveTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_dashboard_exposes_mobile_nav_scaffolding(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Mobile Dash 826');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-dash@sprint826.test']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('op-nav-toggle', false)
            ->assertSee('op-nav-drawer', false)
            ->assertSee('op-nav-backdrop', false)
            ->assertSee('client-mobile.js', false)
            ->assertSee('client-ui.css', false);
    }

    public function test_campaigns_and_financeiro_and_reports_load_with_client_ui(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Mobile CRUD 826');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-crud@sprint826.test']);

        $this->actingAs($admin)->get(route('campaigns.index'))->assertOk()->assertSee('client-ui', false);
        $this->actingAs($admin)->get(route('commissions.index'))->assertOk()->assertSee('Financeiro');
        $this->actingAs($admin)->get(route('reports.index'))->assertOk()->assertSee('Atalhos de análise');
    }

    public function test_team_settings_more_and_properties_use_responsive_shells(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Mobile Shell 826');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-shell@sprint826.test']);

        $this->actingAs($admin)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('op-nav-toggle', false)
            ->assertSee('Equipe');

        $this->actingAs($admin)
            ->get(route('operations.settings'))
            ->assertOk()
            ->assertSee('Configurações');

        $this->actingAs($admin)
            ->get(route('operations.more'))
            ->assertOk()
            ->assertSee('Configurações');

        $this->actingAs($admin)
            ->get(route('properties.index'))
            ->assertOk()
            ->assertSee('shell-nav-toggle', false)
            ->assertSee('client-mobile.js', false);
    }

    public function test_crm_leads_and_map_render(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Mobile CRM 826');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-crm@sprint826.test']);

        $this->actingAs($admin)
            ->get(route('crm.dashboard'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('crm.leads.index'))
            ->assertOk()
            ->assertSee('Leads');

        $this->actingAs($admin)
            ->get(route('map.index'))
            ->assertOk()
            ->assertSee('map-fullscreen', false)
            ->assertSee('op-nav-toggle', false);
    }

    public function test_client_mobile_assets_exist(): void
    {
        $this->assertFileExists(public_path('js/client-mobile.js'));
        $css = file_get_contents(public_path('css/client-ui.css'));
        $this->assertStringContainsString('Sprint 8.2.6', $css);
        $this->assertStringContainsString('client-data-table--responsive', $css);
        $this->assertStringContainsString('--client-touch', $css);
        $this->assertStringContainsString('op-nav-backdrop', $css);
    }
}
