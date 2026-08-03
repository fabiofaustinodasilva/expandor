<?php

namespace Tests\Feature\Platform;

use App\Domains\Branding\DTOs\BrandPayload;
use App\Domains\Branding\Enums\BrandTheme;
use App\Domains\Branding\Services\BrandingContrastService;
use App\Domains\Branding\Services\ThemeService;
use App\Domains\Platform\Models\PlatformBrand;
use App\Domains\Platform\Services\PlatformBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 5.5.1 — separação nome/slogan + auditoria visual login.
 */
class PlatformBrandingSprint551Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_login_does_not_duplicate_name_and_slogan(): void
    {
        $slogan = 'MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA';

        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'slogan' => $slogan,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#0F172A',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
        ]);

        $html = $this->get(route('login'))
            ->assertOk()
            ->assertSee($slogan, false)
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('Acesse sua conta', false)
            ->assertDontSee('Bem-vindo ao '.$slogan, false)
            ->getContent();

        $this->assertSame(1, substr_count($html, $slogan));
        $this->assertSame(1, substr_count($html, 'Bem-vindo ao Expandor'));
        $this->assertStringContainsString('data-platform-logo="1"', $html);
        $this->assertStringContainsString('data-platform-slogan="1"', $html);
        $this->assertStringNotContainsString('class="brand-hero__name"', $html);
    }

    public function test_login_fallback_expandor_without_platform_brand(): void
    {
        $this->assertNull(PlatformBrand::query()->first());

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Login — Expandor', false)
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('data-platform-fallback="1"', false)
            ->assertDontSee('data-platform-slogan="1"', false);
    }

    public function test_login_hides_slogan_space_when_empty(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'slogan' => '',
            'primary_color' => '#2563EB',
            'secondary_color' => '#111827',
            'highlight_color' => '#F97316',
        ], [
            'logo' => UploadedFile::fake()->image('logo.png', 160, 60),
        ]);

        $row = PlatformBrand::query()->first();
        $this->assertNotNull($row);
        $this->assertNull($row->slogan);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertDontSee('data-platform-slogan="1"', false)
            ->assertDontSee('MAPEIE', false);
    }

    public function test_owner_can_save_platform_name_and_slogan_separately(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'slogan' => 'MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA',
                'primary_color' => '#0EA5E9',
                'secondary_color' => '#FFFFFF',
                'highlight_color' => '#EF4444',
            ])
            ->assertRedirect(route('platform.branding.edit'));

        $row = PlatformBrand::query()->first();
        $this->assertSame('Expandor', $row->name);
        $this->assertSame('MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA', $row->slogan);

        $payload = app(PlatformBrandingService::class)->payload();
        $this->assertSame('Expandor', $payload->name());
        $this->assertSame('MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA', $payload->sloganText());
    }

    public function test_contrast_still_works_with_white_secondary(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'slogan' => 'MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA',
            'primary_color' => '#0B1F3A',
            'secondary_color' => '#FFFFFF',
            'highlight_color' => '#EF4444',
        ]);

        $payload = app(PlatformBrandingService::class)->payload();
        $theme = app(ThemeService::class);
        $css = $theme->cssVariables($payload);

        $this->assertStringContainsString('--text: '.BrandingContrastService::DARK_TEXT, $css);
        $this->assertStringContainsString('--button-text: #FFFFFF', $css);
        $this->assertSame('brand-contrast-light', $theme->contrastClass($payload));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('--text: '.BrandingContrastService::DARK_TEXT, false)
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA', false);
    }

    public function test_payload_defaults_keep_expandor_without_slogan(): void
    {
        $defaults = BrandPayload::defaults();

        $this->assertSame('Expandor', $defaults->name());
        $this->assertNull($defaults->sloganText());
        $this->assertSame(BrandTheme::Dark, $defaults->theme);
    }
}
