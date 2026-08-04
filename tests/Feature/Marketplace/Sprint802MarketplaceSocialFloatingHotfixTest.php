<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;
use Database\Seeders\MarketplaceDefaultSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint802MarketplaceSocialFloatingHotfixTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(MarketplaceDefaultSeeder::class);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);
    }

    public function test_whatsapp_configured_appears_and_empty_does_not(): void
    {
        $settings = MarketplaceSetting::query()->firstOrFail();
        $settings->update([
            'whatsapp_enabled' => true,
            'whatsapp_number' => '62999999999',
            'whatsapp_message' => 'Olá! Gostaria de conhecer o Expandor.',
        ]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $encoded = rawurlencode('Olá! Gostaria de conhecer o Expandor. Origem: Marketplace');

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('wa.me/5562999999999', false)
            ->assertSee('?text='.$encoded, false)
            ->assertSee('class="mkp-whatsapp"', false)
            ->assertSee('Falar no WhatsApp', false);

        $settings->update([
            'whatsapp_enabled' => false,
            'whatsapp_number' => null,
        ]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertDontSee('class="mkp-whatsapp"', false)
            ->assertDontSee('wa.me/', false);
    }

    public function test_each_social_network_renders_when_configured(): void
    {
        $settings = MarketplaceSetting::query()->firstOrFail();
        $settings->update([
            'instagram_enabled' => true,
            'instagram_url' => 'https://instagram.com/expandor',
            'facebook_enabled' => true,
            'facebook_url' => 'https://facebook.com/expandor',
            'linkedin_enabled' => true,
            'linkedin_url' => 'https://linkedin.com/company/expandor',
            'youtube_enabled' => true,
            'youtube_url' => 'https://youtube.com/@expandor',
            'tiktok_enabled' => true,
            'tiktok_url' => 'https://tiktok.com/@expandor',
            'twitter_enabled' => true,
            'twitter_url' => 'https://x.com/expandor',
        ]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('https://instagram.com/expandor', false)
            ->assertSee('https://facebook.com/expandor', false)
            ->assertSee('https://linkedin.com/company/expandor', false)
            ->assertSee('https://youtube.com/@expandor', false)
            ->assertSee('https://tiktok.com/@expandor', false)
            ->assertSee('https://x.com/expandor', false)
            ->assertSee('>Instagram<', false)
            ->assertSee('>Facebook<', false)
            ->assertSee('>LinkedIn<', false)
            ->assertSee('>YouTube<', false)
            ->assertSee('>TikTok<', false)
            ->assertSee('>X<', false);
    }

    public function test_components_do_not_render_when_socials_empty(): void
    {
        $settings = MarketplaceSetting::query()->firstOrFail();
        $settings->update([
            'whatsapp_enabled' => false,
            'instagram_enabled' => false,
            'facebook_enabled' => false,
            'linkedin_enabled' => false,
            'youtube_enabled' => false,
            'tiktok_enabled' => false,
            'twitter_enabled' => false,
            'instagram_url' => null,
            'facebook_url' => null,
            'linkedin_url' => null,
            'youtube_url' => null,
            'tiktok_url' => null,
            'twitter_url' => null,
        ]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $html = $this->get(route('marketplace.home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('class="mkp-social"', $html);
        $this->assertStringNotContainsString('class="mkp-whatsapp"', $html);
    }

    public function test_admin_save_reflects_immediately_on_landing_without_manual_cache_clear(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.marketplace.settings.update'), [
                'title' => 'Expandor Social 802',
                'whatsapp_enabled' => '1',
                'whatsapp_number' => '5562888777666',
                'whatsapp_message' => 'Quero demo Expandor',
                'instagram_enabled' => '1',
                'instagram_url' => 'https://instagram.com/expandor802',
                'facebook_enabled' => '1',
                'facebook_url' => 'https://facebook.com/expandor802',
                'linkedin_enabled' => '0',
                'youtube_enabled' => '0',
                'tiktok_enabled' => '1',
                'tiktok_url' => 'https://tiktok.com/@expandor802',
                'twitter_enabled' => '0',
            ])
            ->assertRedirect(route('platform.marketplace.settings.edit'));

        $settings = app(MarketplaceSettingsService::class)->current();
        $this->assertTrue($settings->whatsapp_enabled);
        $this->assertTrue($settings->instagram_enabled);
        $this->assertTrue($settings->tiktok_enabled);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('wa.me/5562888777666', false)
            ->assertSee(rawurlencode('Quero demo Expandor Origem: Marketplace'), false)
            ->assertSee('https://instagram.com/expandor802', false)
            ->assertSee('https://facebook.com/expandor802', false)
            ->assertSee('https://tiktok.com/@expandor802', false)
            ->assertDontSee('https://linkedin.com', false);
    }

    public function test_admin_preview_panel_shows_whatsapp_and_socials(): void
    {
        $settings = MarketplaceSetting::query()->firstOrFail();
        $settings->update([
            'whatsapp_enabled' => true,
            'whatsapp_number' => '5562999999999',
            'whatsapp_message' => 'Olá! Quero conhecer o Expandor.',
            'instagram_enabled' => true,
            'instagram_url' => 'https://instagram.com/expandor.preview',
        ]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.marketplace.settings.edit'))
            ->assertOk()
            ->assertSee('Preview WhatsApp', false)
            ->assertSee('WhatsApp ativo', false)
            ->assertSee('Abrir conversa', false)
            ->assertSee('Preview redes', false)
            ->assertSee('https://instagram.com/expandor.preview', false);
    }

    public function test_content_fallback_still_works_without_socials(): void
    {
        MarketplaceSetting::query()->delete();
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Venda mais', false)
            ->assertDontSee('class="mkp-whatsapp"', false);
    }
}
