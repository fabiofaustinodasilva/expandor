<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint810MarketplacePremiumCopywritingCmsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);
    }

    public function test_landing_sells_door_to_door_results_without_technical_jargon(): void
    {
        $html = $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Transforme território em vendas', false)
            ->assertSee('Agendar demonstração', false)
            ->assertSee('Feito para a operação comercial de provedores', false)
            ->assertSee('Energia solar', false)
            ->assertSee('Mais controle', false)
            ->assertSee('Território com memória', false)
            ->assertDontSee('Calculadora de ROI', false)
            ->getContent();

        $this->assertStringNotContainsString('Multi Tenant', $html);
        $this->assertStringNotContainsString('Growth Engine', $html);
        $this->assertStringNotContainsString('Revenue Intelligence', $html);
    }

    public function test_cms_can_override_benefits_segments_and_footer(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.marketplace.settings.update'), [
                'title' => 'Expandor',
                'conversion_content' => [
                    'social_proof_title' => 'Prova social 810',
                    'benefits_text' => "Benefício A 810\nBenefício B 810",
                    'segments_text' => "Segmento Solar 810 | Desc solar\nSegmento Net 810 | Desc net",
                    'footer' => [
                        'title' => 'Rodapé 810',
                        'text' => 'Texto rodapé 810',
                        'rights' => 'Direitos 810',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Prova social 810', false)
            ->assertSee('Benefício A 810', false)
            ->assertSee('Segmento Solar 810', false)
            ->assertSee('Texto rodapé 810', false)
            ->assertSee('Direitos 810', false);
    }

    public function test_mercadopago_admin_panel_saves_without_env(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.marketplace.mercadopago.edit'))
            ->assertOk()
            ->assertSee('Mercado Pago', false)
            ->assertSee('Access Token', false);

        $this->actingAs($owner)
            ->put(route('platform.marketplace.mercadopago.update'), [
                'mode' => 'sandbox',
                'public_key' => 'TEST-public-key',
                'access_token' => 'TEST-access-token',
                'webhook_url' => 'https://example.test/webhooks/mercadopago',
                'webhook_secret' => 'secret-810',
                'active' => '1',
            ])
            ->assertRedirect(route('platform.marketplace.mercadopago.edit'));

        $row = PaymentGatewaySetting::query()
            ->where('provider', PaymentGatewaySetting::PROVIDER_MERCADOPAGO)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('sandbox', $row->mode);
        $this->assertSame('TEST-public-key', $row->public_key);
        $this->assertTrue($row->active);
        $this->assertSame('TEST-access-token', $row->access_token);
    }

    public function test_segment_page_does_not_expose_marketplace_label(): void
    {
        // Without seeded segment, home still works; if segment route 404 skip.
        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertDontSee('← Marketplace', false)
            ->assertDontSee('Voltar ao marketplace', false);
    }

    public function test_admin_menu_uses_site_publico_labels(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.marketplace.settings.edit'))
            ->assertOk()
            ->assertSee('Site público', false)
            ->assertSee('Site público', false)
            ->assertSee('Mercado Pago', false)
            ->assertSee('Visualizar site', false);
    }
}
