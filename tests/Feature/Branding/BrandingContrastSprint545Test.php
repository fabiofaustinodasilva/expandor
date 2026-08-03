<?php

namespace Tests\Feature\Branding;

use App\Domains\Branding\DTOs\BrandPayload;
use App\Domains\Branding\Enums\BrandTheme;
use App\Domains\Branding\Services\BrandingContrastService;
use App\Domains\Branding\Services\ThemeService;
use App\Domains\Platform\Services\PlatformBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class BrandingContrastSprint545Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_white_background_generates_dark_text(): void
    {
        $contrast = app(BrandingContrastService::class);
        $tokens = $contrast->resolve('#FFFFFF');

        $this->assertTrue($tokens['is_light']);
        $this->assertSame(BrandingContrastService::DARK_TEXT, $tokens['text_primary']);
        $this->assertSame(BrandingContrastService::DARK_MUTED, $tokens['muted_text']);
        $this->assertSame(BrandingContrastService::DARK_TEXT, $tokens['button_text']);
        $this->assertSame('brand-contrast-light', $tokens['css_class']);
    }

    public function test_dark_background_generates_light_text(): void
    {
        $contrast = app(BrandingContrastService::class);

        foreach (['#000000', '#0F1117', '#171A22', '#0B1F3A'] as $hex) {
            $tokens = $contrast->resolve($hex);
            $this->assertFalse($tokens['is_light'], "Expected dark surface for {$hex}");
            $this->assertSame(BrandingContrastService::LIGHT_TEXT, $tokens['text_primary']);
            $this->assertSame(BrandingContrastService::LIGHT_MUTED, $tokens['muted_text']);
            $this->assertSame('#FFFFFF', $tokens['button_text']);
            $this->assertSame('brand-contrast-dark', $tokens['css_class']);
        }
    }

    public function test_theme_service_applies_contrast_when_secondary_is_white(): void
    {
        $theme = app(ThemeService::class);
        $defaults = BrandPayload::defaults();

        $brand = new BrandPayload(
            systemName: 'Expandor',
            displayName: 'Expandor',
            slogan: null,
            logoUrl: null,
            logoMarkUrl: null,
            faviconUrl: null,
            loginImageUrl: null,
            colors: $theme->normalizeColors(array_merge($defaults->colors, [
                'primary' => '#0B1F3A',
                'secondary' => '#FFFFFF',
                'highlight' => '#EF4444',
                'bg_elevated' => '#FFFFFF',
            ])),
            theme: BrandTheme::Dark,
            fonts: $defaults->fonts,
            supportEmail: null,
            supportPhone: null,
            socials: $defaults->socials,
            customDomain: null,
            customCss: null,
            isCustomized: true,
            companyId: null,
        );

        $css = $theme->cssVariables($brand);

        $this->assertStringContainsString('--text: '.BrandingContrastService::DARK_TEXT, $css);
        $this->assertStringContainsString('--muted: '.BrandingContrastService::DARK_MUTED, $css);
        $this->assertStringContainsString('--button-text: #FFFFFF', $css);
        $this->assertStringContainsString('--bg-elevated: #FFFFFF', $css);
        $this->assertSame('brand-contrast-light', $theme->contrastClass($brand));
    }

    public function test_login_renders_contrast_tokens_for_existing_branding(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#0B1F3A',
            'secondary_color' => '#FFFFFF',
            'highlight_color' => '#EF4444',
        ]);

        $html = $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor', false)
            ->getContent();

        $this->assertStringContainsString('--text: #0F172A', $html);
        $this->assertStringContainsString('--muted: #64748B', $html);
        $this->assertStringContainsString('--button-text:', $html);
        $this->assertStringContainsString('brand-contrast-light', $html);
        $this->assertStringContainsString('color: var(--button-text)', $html);
    }

    public function test_default_dark_branding_keeps_light_text(): void
    {
        $css = app(ThemeService::class)->cssVariables(BrandPayload::defaults());

        $this->assertStringContainsString('--text: '.BrandingContrastService::LIGHT_TEXT, $css);
        $this->assertStringContainsString('--muted: '.BrandingContrastService::LIGHT_MUTED, $css);
        $this->assertSame('brand-contrast-dark', app(ThemeService::class)->contrastClass(BrandPayload::defaults()));
    }
}
