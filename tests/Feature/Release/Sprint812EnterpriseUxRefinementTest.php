<?php

namespace Tests\Feature\Release;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint812EnterpriseUxRefinementTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_platform_nav_is_portuguese_and_collapsible(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('nav-section-toggle', false)
            ->assertSee('Site público', false)
            ->assertSee('Aquisição', false)
            ->assertSee('Análises', false)
            ->assertSee('Funil comercial', false)
            ->assertDontSee('>Dashboard<', false)
            ->assertDontSee('SaaS Health', false)
            ->assertDontSee('Geral & Landing', false)
            ->assertDontSee('>Billing<', false)
            ->assertDontSee('Feature Flags', false);
    }

    public function test_checkout_has_premium_layout_sections(): void
    {
        $plan = \App\Domains\Company\Models\Plan::query()
            ->where('status', \App\Domains\Company\Models\Plan::STATUS_ACTIVE)
            ->where('price', '>', 0)
            ->firstOrFail();

        $this->get(route('checkout.create', ['plan_id' => $plan->id]))
            ->assertOk()
            ->assertSee('Finalizar assinatura', false)
            ->assertSee('Resumo do plano', false)
            ->assertSee('Dados da empresa', false)
            ->assertSee('Dados do responsável', false)
            ->assertSee('Confirmar e ir para pagamento', false);
    }

    public function test_landing_avoids_marketplace_and_trial_jargon(): void
    {
        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Começar agora', false)
            ->assertDontSee('Começar teste grátis', false)
            ->assertDontSee('Configuração do Marketplace', false)
            ->assertDontSee('Página pública pronta', false)
            ->assertDontSee('converter visitantes', false);
    }

    public function test_site_settings_page_uses_expandor_language(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.marketplace.settings.edit'))
            ->assertOk()
            ->assertSee('Site Expandor', false)
            ->assertDontSee('Configuração do Marketplace', false);
    }
}
