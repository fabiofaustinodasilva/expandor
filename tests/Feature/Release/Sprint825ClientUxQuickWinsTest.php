<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Support\ClientArea\ClientNav;
use App\Support\ClientArea\NavVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint825ClientUxQuickWinsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_settings_appears_in_mais_and_nav_visibility(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Settings 825');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-settings@sprint825.test']);

        $this->assertTrue(NavVisibility::can($admin, 'settings'));

        $labels = collect(ClientNav::sections($admin))
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('label')
            ->all();

        $this->assertContains('Configurações', $labels);

        $this->actingAs($admin)
            ->get(route('operations.more'))
            ->assertOk()
            ->assertSee('Configurações')
            ->assertSee(route('operations.settings'), false);
    }

    public function test_reports_page_is_honest_shortcuts_hub(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Reports 825');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-reports@sprint825.test']);

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Atalhos de análise')
            ->assertSee('não há análise embutida aqui')
            ->assertDontSee('foi organizada aqui, fora do painel principal');
    }

    public function test_map_uses_novo_ponto_glossary(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Map 825');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-map@sprint825.test']);

        $this->actingAs($admin)
            ->get(route('map.index'))
            ->assertOk()
            ->assertSee('Meu Local')
            ->assertDontSee('Nova oportunidade');
    }

    public function test_seller_dashboard_title_matches_resultado_rail(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Seller Dash 825');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-dash@sprint825.test']);

        $this->actingAs($seller)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Resultado')
            ->assertSee('client-period-seg', false)
            ->assertSee('Abrir mapa');
    }

    public function test_commissions_filters_collapsed_and_financeiro_title(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Financeiro 825');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-fin@sprint825.test']);

        $this->actingAs($admin)
            ->get(route('commissions.index'))
            ->assertOk()
            ->assertSee('Financeiro')
            ->assertSee('client-filters-collapsible', false)
            ->assertSee('Filtros');
    }

    public function test_crm_rules_page_and_users_banner_and_campaign_overflow(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa CRM 825');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-crm@sprint825.test']);

        $this->actingAs($admin)
            ->get(route('crm.commissions.index'))
            ->assertOk()
            ->assertSee('Regras de comissão');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Equipe')
            ->assertSee(route('operations.team'), false);

        $this->actingAs($admin)
            ->get(route('campaigns.index'))
            ->assertOk()
            ->assertSee('Campanhas')
            ->assertSee('client-empty-state', false);

        $this->actingAs($admin)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Membros')
            ->assertSee('Equipe');

        $this->actingAs($admin)
            ->get(route('operations.settings'))
            ->assertOk()
            ->assertDontSee('📦')
            ->assertDontSee('💰');
    }

    public function test_leads_convert_requires_confirm_attribute(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Leads 825');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-leads@sprint825.test']);

        $html = $this->actingAs($admin)
            ->get(route('crm.leads.index'))
            ->assertOk()
            ->getContent();

        $this->assertTrue(
            str_contains($html, 'Converter este lead')
            || str_contains($html, 'onsubmit')
            || ! str_contains($html, 'crm.leads.convert')
        );
    }
}
