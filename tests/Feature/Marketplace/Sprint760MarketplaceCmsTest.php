<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Marketplace\Enums\MarketplaceSectionType;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Models\MarketplaceSection;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;
use Database\Seeders\MarketplaceCmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint760MarketplaceCmsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(MarketplaceCmsSeeder::class);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);
    }

    public function test_configuration_is_saved(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.marketplace.settings.update'), [
                'title' => 'Título Premium CMS',
                'subtitle' => 'Subtítulo comercial',
                'primary_color' => '#112233',
                'secondary_color' => '#445566',
                'background_color' => '#0A0B0C',
                'button_color' => '#FFAA00',
                'seo_title' => 'SEO Title Expandor',
                'seo_description' => 'Descrição SEO do marketplace',
                'seo_keywords' => 'crm, saas',
                'whatsapp_enabled' => true,
                'whatsapp_number' => '5511999998888',
                'whatsapp_message' => 'Quero testar',
                'instagram_enabled' => true,
                'instagram_url' => 'https://instagram.com/expandor',
            ])
            ->assertRedirect(route('platform.marketplace.settings.edit'));

        $settings = app(MarketplaceSettingsService::class)->current();

        $this->assertSame('Título Premium CMS', $settings->title);
        $this->assertSame('#112233', $settings->primary_color);
        $this->assertTrue($settings->whatsapp_enabled);
        $this->assertSame('5511999998888', $settings->whatsapp_number);
        $this->assertTrue($settings->instagram_enabled);
    }

    public function test_public_landing_applies_colors_and_seo(): void
    {
        $settings = MarketplaceSetting::query()->firstOrFail();
        $settings->update([
            'title' => 'Operação inteligente Expandor',
            'primary_color' => '#123456',
            'button_color' => '#FEDCBA',
            'seo_title' => 'Meta Title Marketplace',
            'seo_description' => 'Meta Description Marketplace',
            'logo' => null,
        ]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $response = $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Meta Title Marketplace', false)
            ->assertSee('Meta Description Marketplace', false)
            ->assertSee('--mkp-primary: #123456', false)
            ->assertSee('--mkp-button: #FEDCBA', false)
            ->assertSee('Operação inteligente Expandor');

        $this->assertDatabaseHas('marketplace_events', [
            'event' => MarketplaceAnalyticsService::PAGE_VIEW,
        ]);

        $this->assertNotNull($response->getContent());
    }

    public function test_whatsapp_button_appears_when_enabled_and_hides_when_disabled(): void
    {
        $settings = MarketplaceSetting::query()->firstOrFail();
        $settings->update([
            'whatsapp_enabled' => true,
            'whatsapp_number' => '5511888777666',
            'whatsapp_message' => 'Olá Expandor',
        ]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('wa.me/5511888777666', false)
            ->assertSee('data-mkp-event="marketplace.whatsapp_clicked"', false)
            ->assertSee('Falar no WhatsApp', false);

        $settings->update(['whatsapp_enabled' => false]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertDontSee('wa.me/5511888777666', false)
            ->assertDontSee('Falar no WhatsApp', false);
    }

    public function test_instagram_social_link_renders(): void
    {
        $settings = MarketplaceSetting::query()->firstOrFail();
        $settings->update([
            'instagram_enabled' => true,
            'instagram_url' => 'https://instagram.com/expandor.oficial',
        ]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('https://instagram.com/expandor.oficial', false)
            ->assertSee('marketplace.instagram_clicked', false);
    }

    public function test_sections_can_be_activated_and_ordered(): void
    {
        $owner = $this->makePlatformAdmin();

        $about = MarketplaceSection::query()
            ->where('type', MarketplaceSectionType::About->value)
            ->firstOrFail();
        $features = MarketplaceSection::query()
            ->where('type', MarketplaceSectionType::Features->value)
            ->firstOrFail();

        $this->actingAs($owner)
            ->post(route('platform.marketplace.sections.toggle', $about))
            ->assertRedirect();

        $about->refresh();
        $this->assertFalse($about->active);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertDontSee('id="quem-somos"', false);

        $this->actingAs($owner)
            ->post(route('platform.marketplace.sections.toggle', $about))
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('platform.marketplace.sections.reorder'), [
                'order' => [$features->id, $about->id],
            ])
            ->assertRedirect();

        $features->refresh();
        $about->refresh();
        $this->assertSame(1, $features->order);
        $this->assertSame(2, $about->order);
    }

    public function test_analytics_events_are_registered_via_endpoint(): void
    {
        $this->postJson(route('marketplace.events.store'), [
            'event' => MarketplaceAnalyticsService::WHATSAPP_CLICKED,
            'metadata' => ['source' => 'test'],
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('marketplace_events', [
            'event' => MarketplaceAnalyticsService::WHATSAPP_CLICKED,
        ]);

        $event = MarketplaceEvent::query()
            ->where('event', MarketplaceAnalyticsService::WHATSAPP_CLICKED)
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertNotNull($event->ip_hash);
        $this->assertNotNull($event->created_at);
    }

    public function test_platform_admin_can_open_cms_and_preview(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.marketplace.settings.edit'))
            ->assertOk()
            ->assertSee('Configuração');

        $this->actingAs($owner)
            ->get(route('platform.marketplace.sections.index'))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('platform.marketplace.media.index'))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('platform.marketplace.preview'))
            ->assertOk()
            ->assertSee('preview', false);
    }

    public function test_sitemap_is_available(): void
    {
        $this->get(route('marketplace.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(url('/'), false);
    }
}
