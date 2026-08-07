<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint823ClientUxRefinementTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_dashboard_filters_are_collapsed_by_default_and_finance_metric_is_removed(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Dashboard Slim 823');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-slim@sprint823.test']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Filtros')
            ->assertSee('client-filters-collapsible', false)
            ->assertDontSee('Financeiro do mês')
            ->assertDontSee('Funil comercial');
    }

    public function test_dashboard_shows_skip_link_for_accessibility(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa A11y 823');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-a11y@sprint823.test']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('client-skip-link', false)
            ->assertSee('Ir para o conteúdo')
            ->assertSee('id="client-main"', false);
    }

    public function test_campaigns_index_still_renders_with_crud_toolbar(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Campanhas 823');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-campaigns@sprint823.test']);

        $this->actingAs($admin)
            ->get(route('campaigns.index'))
            ->assertOk()
            ->assertSee('Campanhas')
            ->assertSee('client-crud-toolbar', false);
    }

    public function test_customers_index_still_renders_with_crud_toolbar(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Clientes 823');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-customers@sprint823.test']);

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Clientes')
            ->assertSee('client-crud-toolbar', false);
    }

    public function test_app_layout_and_operational_layout_expose_client_ui_scaffolding(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Layout 823');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-layout@sprint823.test']);

        // layouts.operational (dashboard)
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('client-ui', false);

        // layouts.app (pontos / properties)
        $this->actingAs($admin)
            ->get(route('properties.index'))
            ->assertOk()
            ->assertSee('client-ui', false)
            ->assertSee('client-skip-link', false);
    }

    public function test_new_client_components_render_without_errors(): void
    {
        $html = Blade::render('<x-client.alert type="success">Tudo certo</x-client.alert>');
        $this->assertStringContainsString('client-alert--success', $html);
        $this->assertStringContainsString('Tudo certo', $html);

        $html = Blade::render('<x-client.skeleton variant="title" />');
        $this->assertStringContainsString('client-skeleton--title', $html);

        $html = Blade::render('<x-client.loading />');
        $this->assertStringContainsString('aria-busy="true"', $html);

        $html = Blade::render('<x-client.tooltip text="Ajuda extra">Rótulo</x-client.tooltip>');
        $this->assertStringContainsString('client-tooltip__bubble', $html);
        $this->assertStringContainsString('Ajuda extra', $html);

        $html = Blade::render('<x-client.crud-toolbar><x-slot:actions>Ação</x-slot:actions></x-client.crud-toolbar>');
        $this->assertStringContainsString('client-crud-toolbar__actions', $html);

        $html = Blade::render('<x-client.pagination-bar>Nav de páginas</x-client.pagination-bar>');
        $this->assertStringContainsString('client-pagination', $html);
    }
}
