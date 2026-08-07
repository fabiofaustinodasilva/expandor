<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Support\ClientArea\NavVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint822ClientUiImplementationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_nav_visibility_hides_ai_without_permission(): void
    {
        // Plano Enterprise libera a feature de IA no catálogo, então o
        // resultado abaixo isola exclusivamente a checagem de permissão.
        $company = $this->makeCompanyWithPlan('Empresa Sem IA', 'enterprise');

        $viewer = $this->makeUser($company, Role::VIEWER, ['email' => 'viewer-ai@sprint822.test']);
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-ai@sprint822.test']);

        $this->assertFalse(NavVisibility::can($viewer, 'ai'));
        $this->assertTrue(NavVisibility::can($admin, 'ai'));
    }

    public function test_dashboard_shows_slim_metrics_not_funnel_heading(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Dashboard Slim');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-slim@sprint822.test']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Visitas')
            ->assertSee('Ver relatórios')
            ->assertDontSee('Funil comercial')
            ->assertDontSee('Resultados por status')
            ->assertDontSee('chart-visits-period', false);
    }

    public function test_app_nav_uses_pontos_label(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Nav Pontos');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-nav@sprint822.test']);

        $this->actingAs($admin)
            ->get(route('cities.index'))
            ->assertOk()
            ->assertSee('Pontos')
            ->assertDontSee('Usuários (técnico)');
    }

    public function test_reports_route_renders(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Relatórios');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-reports@sprint822.test']);

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Relatórios');
    }

    public function test_team_hub_shows_sections(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Equipe Hub 822');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-team-822@sprint822.test']);

        $this->actingAs($admin)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Usuários')
            ->assertSee('Funções')
            ->assertSee('Permissões')
            ->assertSee('Metas')
            ->assertSee('Comissões');
    }
}
