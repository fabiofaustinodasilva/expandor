<?php

namespace Tests\Feature\Release;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint811ProductionQaVisualPolishTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_design_system_tokens_are_present_on_platform(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('--ds-radius', false)
            ->assertSee('btn-primary', false)
            ->assertSee('shell-nav-toggle', false)
            ->assertSee('ux-toast-host', false)
            ->assertSee(':focus-visible', false)
            ->assertDontSee('Administração SaaS', false);
    }

    public function test_sales_app_includes_shared_design_system(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Campo Polish');
        $seller = $this->makeUser($company, \App\Domains\Company\Models\Role::SELLER, [
            'email' => 'seller-polish@expandor.test',
        ]);

        $this->actingAs($seller)
            ->get(route('sales-app.dashboard'))
            ->assertOk()
            ->assertSee('ux-toast-host', false)
            ->assertSee('ExpandorUX', false)
            ->assertSee('aria-label="Navegação do app de campo"', false);
    }

    public function test_landing_seo_fallbacks_avoid_technical_jargon(): void
    {
        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertDontSee('CRM inteligente', false)
            ->assertDontSee('crm, vendas, saas, expandor', false)
            ->assertSee('mkp-menu-toggle', false)
            ->assertSee('vendas porta a porta', false);
    }

    public function test_demo_form_uses_neutral_css_class_names(): void
    {
        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('mkp-demo-card', false)
            ->assertDontSee('mkp-roi-card', false)
            ->assertDontSee('mkp-growth-section', false);
    }
}
