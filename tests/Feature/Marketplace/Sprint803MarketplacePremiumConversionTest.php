<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Marketplace\Enums\MarketplaceSectionType;
use App\Domains\Marketplace\Models\MarketplaceSection;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use App\Domains\Marketplace\Services\MarketplacePublicPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint803MarketplacePremiumConversionTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);
        Cache::forget('marketplace.public.metrics.v1');
    }

    public function test_landing_loads_premium_defaults_without_cms(): void
    {
        $this->assertSame(0, MarketplaceSection::query()->count());

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Organize sua equipe de vendas porta a porta', false)
            ->assertSee('Começar teste grátis', false)
            ->assertSee('Solicitar demonstração', false)
            ->assertSee('Veja o Expandor em ação', false)
            ->assertSee('Como funciona', false)
            ->assertSee('Cadastre sua equipe', false)
            ->assertSee('Vendedores sem acompanhamento', false)
            ->assertSee('Equipe organizada e visível', false)
            ->assertSee('Empresas organizam suas equipes de campo com Expandor', false)
            ->assertSee('/images/marketplace/screens/dashboard.svg', false)
            ->assertSee('Expandor — Sistema para vendas porta a porta', false)
            ->assertSee('Organize vendedores, visitas e clientes', false)
            ->assertSee('data-mkp-carousel', false)
            ->assertSee('mkp-menu-toggle', false);
    }

    public function test_cms_overrides_premium_defaults(): void
    {
        MarketplaceSection::query()->create([
            'type' => MarketplaceSectionType::Hero->value,
            'title' => 'Hero Premium CMS 803',
            'subtitle' => 'Sub CMS 803',
            'button_text' => 'CTA CMS 803',
            'button_url' => '/cadastro',
            'order' => 1,
            'active' => true,
        ]);

        $settings = app(MarketplaceSettingsRepository::class)->current();
        $settings->update([
            'seo_title' => 'SEO Custom 803',
            'seo_description' => 'Desc SEO Custom 803',
            'demo_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'conversion_content' => [
                'social_proof_title' => 'Prova social CMS 803',
                'before_after' => [
                    'before' => ['problema cms'],
                    'after' => ['solucao cms'],
                ],
            ],
        ]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Hero Premium CMS 803', false)
            ->assertSee('CTA CMS 803', false)
            ->assertSee('SEO Custom 803', false)
            ->assertSee('Desc SEO Custom 803', false)
            ->assertSee('Prova social CMS 803', false)
            ->assertSee('problema cms', false)
            ->assertSee('solucao cms', false)
            ->assertSee('data-mkp-demo-open', false)
            ->assertSee('youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Hero Premium CMS 803', false);
    }

    public function test_assemble_exposes_metrics_and_showcase(): void
    {
        $page = app(MarketplacePublicPageService::class)->assemble();

        $this->assertNotEmpty($page['premium']['showcase']);
        $this->assertCount(6, $page['premium']['how_it_works']);
        $this->assertNotEmpty($page['metrics']);
        $this->assertTrue(collect($page['metrics'])->contains(fn ($m) => $m['key'] === 'sellers'));
    }

    public function test_plans_page_shows_recommended_and_cta(): void
    {
        $this->get(route('marketplace.plans'))
            ->assertOk()
            ->assertSee('Compare os planos Expandor', false)
            ->assertSee('Começar teste grátis', false);
    }

    public function test_admin_can_save_demo_video_and_conversion_content(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.marketplace.settings.update'), [
                'title' => 'Hero Admin 803',
                'subtitle' => 'Sub Admin',
                'demo_video_url' => 'https://vimeo.com/123456789',
                'seo_title' => 'Expandor - Plataforma inteligente para vendas externas',
                'seo_description' => 'Gerencie vendedores, clientes, campanhas e resultados com inteligência.',
                'conversion_content' => [
                    'social_proof_title' => 'Titulo prova admin',
                    'before_text' => "antes A\nantes B",
                    'after_text' => "depois A\ndepois B",
                    'how_it_works_text' => "Passo 1 | Desc 1\nPasso 2 | Desc 2",
                ],
            ])
            ->assertRedirect(route('platform.marketplace.settings.edit'));

        $settings = MarketplaceSetting::query()->firstOrFail();
        $this->assertSame('https://vimeo.com/123456789', $settings->demo_video_url);
        $this->assertSame('Titulo prova admin', $settings->conversion_content['social_proof_title'] ?? null);
        $this->assertSame(['antes A', 'antes B'], $settings->conversion_content['before_after']['before'] ?? null);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Titulo prova admin', false)
            ->assertSee('player.vimeo.com/video/123456789', false);
    }
}
