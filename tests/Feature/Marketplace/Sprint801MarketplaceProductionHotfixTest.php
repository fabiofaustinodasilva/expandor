<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Marketplace\Enums\MarketplaceSectionType;
use App\Domains\Marketplace\Models\MarketplaceFaq;
use App\Domains\Marketplace\Models\MarketplaceSection;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Models\MarketplaceTestimonial;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use App\Domains\Marketplace\Services\MarketplacePublicPageService;
use Database\Seeders\MarketplaceDefaultSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint801MarketplaceProductionHotfixTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);
    }

    public function test_landing_works_with_empty_cms_database(): void
    {
        $this->assertSame(0, MarketplaceSection::query()->count());
        $this->assertSame(0, MarketplaceTestimonial::query()->count());
        $this->assertSame(0, MarketplaceFaq::query()->count());

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Organize sua equipe de vendas porta a porta', false)
            ->assertSee('Começar agora', false)
            ->assertSee('Solicitar demonstração', false)
            ->assertSee('Mapa inteligente', false)
            ->assertSee('Posso testar sem cartão?', false)
            ->assertSee('Ana Ribeiro', false)
            ->assertSee('Carla Souza', false)
            ->assertSee('id="faq"', false)
            ->assertSee('id="clientes"', false)
            ->assertSee('/images/marketplace/screens/dashboard.svg', false);
    }

    public function test_default_hero_features_faq_and_testimonials_appear(): void
    {
        $page = app(MarketplacePublicPageService::class)->assemble();

        $this->assertNotEmpty($page['sections']);
        $this->assertGreaterThanOrEqual(3, $page['testimonials']->count());
        $this->assertGreaterThanOrEqual(3, $page['faqs']->count());

        $hero = $page['sections']->first(
            fn ($section) => $section->type === MarketplaceSectionType::Hero
        );
        $this->assertNotNull($hero);
        $this->assertStringContainsString('Organize sua equipe de vendas porta a porta', (string) $hero->title);

        $features = $page['sections']->first(
            fn ($section) => $section->type === MarketplaceSectionType::Features
        );
        $items = json_decode((string) $features->description, true);
        $this->assertIsArray($items);
        $this->assertGreaterThanOrEqual(8, count($items));
    }

    public function test_cms_overrides_defaults(): void
    {
        MarketplaceSection::query()->create([
            'type' => MarketplaceSectionType::Hero->value,
            'title' => 'Hero CMS Customizado 801',
            'subtitle' => 'Sub CMS',
            'button_text' => 'CTA CMS',
            'button_url' => '/cadastro',
            'order' => 1,
            'active' => true,
        ]);

        MarketplaceTestimonial::query()->create([
            'name' => 'Cliente CMS 801',
            'company' => 'Empresa CMS',
            'text' => 'Depoimento exclusivo do CMS.',
            'rating' => 5,
            'active' => true,
            'order' => 1,
        ]);

        MarketplaceFaq::query()->create([
            'question' => 'Pergunta CMS 801?',
            'answer' => 'Resposta CMS 801.',
            'order' => 1,
            'active' => true,
        ]);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Hero CMS Customizado 801', false)
            ->assertSee('CTA CMS', false)
            ->assertSee('Cliente CMS 801', false)
            ->assertSee('Pergunta CMS 801?', false)
            ->assertDontSee('Ana Ribeiro', false);
    }

    public function test_seeder_is_idempotent(): void
    {
        $seeder = new MarketplaceDefaultSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertSame(1, MarketplaceSetting::query()->count());
        $this->assertSame(count(config('marketplace_defaults.section_order')), MarketplaceSection::query()->count());
        $this->assertSame(count(config('marketplace_defaults.testimonials')), MarketplaceTestimonial::query()->count());
        $this->assertSame(count(config('marketplace_defaults.faqs')), MarketplaceFaq::query()->count());
    }

    public function test_preview_never_renders_empty_page(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.marketplace.preview'))
            ->assertOk()
            ->assertSee('Modo preview', false)
            ->assertSee('Organize sua equipe de vendas porta a porta', false)
            ->assertSee('Tudo que sua operação de campo precisa', false)
            ->assertSee('Dúvidas frequentes', false)
            ->assertSee('Carla Souza', false);
    }

    public function test_restore_defaults_button_forces_seed(): void
    {
        MarketplaceSection::query()->create([
            'type' => MarketplaceSectionType::Hero->value,
            'title' => 'Hero Antigo',
            'order' => 1,
            'active' => true,
        ]);

        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->post(route('platform.marketplace.settings.restore'))
            ->assertRedirect(route('platform.marketplace.settings.edit'));

        $this->assertDatabaseMissing('marketplace_sections', ['title' => 'Hero Antigo']);
        $this->assertDatabaseHas('marketplace_sections', [
            'type' => MarketplaceSectionType::Hero->value,
        ]);

        $hero = MarketplaceSection::query()
            ->where('type', MarketplaceSectionType::Hero->value)
            ->first();
        $this->assertNotNull($hero);
        $this->assertStringContainsString('Organize sua equipe de vendas porta a porta', (string) $hero->title);
    }
}
