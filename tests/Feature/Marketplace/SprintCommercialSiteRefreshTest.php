<?php

namespace Tests\Feature\Marketplace;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SprintCommercialSiteRefreshTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_landing_does_not_advertise_a_free_plan(): void
    {
        $html = $this->get(route('marketplace.home'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Começar agora', $html);
        $this->assertStringNotContainsString('Começar grátis', $html);
        $this->assertStringNotContainsString('>Grátis<', $html);
        $this->assertStringNotContainsString('Posso testar sem cartão?', $html);
        $this->assertStringContainsString('Existe plano grátis?', $html);
        $this->assertStringContainsString('Não. O Expandor não oferece plano gratuito', $html);
    }

    public function test_landing_shows_commercial_plans_and_demo_cta(): void
    {
        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('R$ 349', false)
            ->assertSee('R$ 449', false)
            ->assertSee('R$ 649', false)
            ->assertSee('Mais escolhido', false)
            ->assertSee('Start', false)
            ->assertSee('Pro', false)
            ->assertSee('Scale', false)
            ->assertSee('Enterprise', false)
            ->assertSee('Sob consulta', false)
            ->assertSee('Até 2 vendedores', false)
            ->assertSee('Até 5 vendedores', false)
            ->assertSee('Vendedores ilimitados', false)
            ->assertSee('Todo o poder do Expandor', false)
            ->assertSee('política de uso justo', false)
            ->assertSee('Agendar demonstração', false)
            ->assertSee('id="demo"', false)
            ->assertDontSee('Assinar agora', false)
            ->assertDontSee('Comprar agora', false);
    }

    public function test_landing_uses_real_product_screenshots_and_field_ops_tabs(): void
    {
        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('/images/marketplace/product/hero-mapa.webp', false)
            ->assertSee('/images/marketplace/product/exp-mapa.webp', false)
            ->assertSee('/images/marketplace/product/exp-agenda.webp', false)
            ->assertSee('/images/marketplace/product/exp-produtos.webp', false)
            ->assertSee('/images/marketplace/product/exp-venda-realizada.webp', false)
            ->assertSee('/images/marketplace/product/exp-resultado.webp', false)
            ->assertSee('/images/marketplace/product/exp-comissao.webp', false)
            ->assertSee('/images/marketplace/product/dashboard-gestor.webp', false)
            ->assertSee('/images/marketplace/product/inteligencia-ponto.webp', false)
            ->assertSee('data-mkp-field-ops', false)
            ->assertSee('Cada imóvel vira uma oportunidade acompanhável.', false)
            ->assertSee('Da rua ao fechamento. Tudo conectado.', false)
            ->assertSee('Enquanto o vendedor está na rua, o gestor enxerga a operação.', false)
            ->assertSee('Não é apenas um mapa.', false)
            ->assertSee('A venda não termina na porta do cliente.', false)
            ->assertDontSee('/images/marketplace/screens/dashboard.svg', false)
            ->assertDontSee('alt="screenshot"', false);
    }

    public function test_landing_answers_erp_objection_and_positions_for_isps(): void
    {
        $html = $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('O Expandor substitui meu ERP?', false)
            ->assertSee('não precisa substituir o sistema de gestão do provedor', false)
            ->assertSee('Transforme território em vendas', false)
            ->assertSee('provedores', false)
            ->assertSee('CRM para provedores', false)
            ->assertDontSee('Demonstração do CRM', false)
            ->assertDontSee('Ana Ribeiro', false)
            ->getContent();

        $this->assertStringNotContainsString('IXC', $html);
        $this->assertStringNotContainsString('MK-Auth', $html);
        $this->assertStringNotContainsString('tempo real', $html);
    }

    public function test_plans_page_mirrors_commercial_pricing_without_free_plan(): void
    {
        $this->get(route('marketplace.plans'))
            ->assertOk()
            ->assertSee('R$ 349', false)
            ->assertSee('R$ 449', false)
            ->assertSee('R$ 649', false)
            ->assertSee('Mais escolhido', false)
            ->assertSee('Enterprise', false)
            ->assertSee('Agendar demonstração', false)
            ->assertDontSee('Começar agora', false)
            ->assertDontSee('>Grátis<', false)
            ->assertDontSee('Free', false);
    }

    public function test_commercial_layout_is_responsive_and_accessible(): void
    {
        $html = $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('width="390"', false)
            ->assertSee('height="844"', false)
            ->assertSee('overflow-x: clip', false)
            ->assertSee('mkp-plans-grid-commercial', false)
            ->assertSee('prefers-reduced-motion', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('Mapa operacional do Expandor com oportunidades comerciais distribuídas no território', false)
            ->getContent();

        $this->assertStringContainsString('@media (max-width: 768px)', $html);
        $this->assertStringContainsString('grid-template-columns: 1fr', $html);
    }
}
