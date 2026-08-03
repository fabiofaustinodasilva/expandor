<?php

namespace Tests\Feature\Auth;

use App\Domains\Platform\Services\PlatformBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Renderização da logo Expandor na tela de login (Sprint 5.4.3 login branding).
 */
class LoginPlatformBrandingRenderTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_login_renders_large_platform_logo_when_configured(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#171A22',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('expandor-login.png', 320, 120),
        ]);

        $logoUrl = app(PlatformBrandingService::class)->payload()->logoUrl;
        $this->assertNotNull($logoUrl);
        $this->assertStringContainsString('platform/branding/', $logoUrl);

        $html = $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-platform-logo="1"', false)
            ->assertSee($logoUrl, false)
            ->assertSee('alt="Expandor"', false)
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('Acesse sua conta', false)
            ->assertSee('Usuário', false)
            ->assertSee('Senha', false)
            ->assertSee('Lembrar-me', false)
            ->assertSee('Entrar', false)
            ->assertDontSee('data-platform-fallback="1"', false)
            ->assertDontSee('companies/')
            ->getContent();

        $this->assertMatchesRegularExpression('/brand-hero__logo[\s\S]*max-height:\s*96px/', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('method="POST"', $html);
    }

    public function test_login_renders_expandor_wordmark_fallback_without_logo(): void
    {
        $html = $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-platform-fallback="1"', false)
            ->assertSee('Expandor', false)
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('Acesse sua conta', false)
            ->assertDontSee('data-platform-logo="1"', false)
            ->getContent();

        $this->assertStringContainsString('brand-hero__wordmark', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_login_keeps_auth_form_contract_unchanged(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('type="submit"', false);
    }

    public function test_login_uses_custom_platform_name_with_logo(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor SaaS',
            'primary_color' => '#0EA5E9',
            'secondary_color' => '#0F172A',
            'highlight_color' => '#F97316',
        ], [
            'logo' => UploadedFile::fake()->image('custom.png', 240, 90),
        ]);

        $logoUrl = app(PlatformBrandingService::class)->payload()->logoUrl;

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Login — Expandor SaaS', false)
            ->assertSee('alt="Expandor SaaS"', false)
            ->assertSee('Bem-vindo ao Expandor SaaS', false)
            ->assertSee('Ainda não conhece o Expandor SaaS?', false)
            ->assertSee($logoUrl, false)
            ->assertSee('data-platform-logo="1"', false)
            ->assertDontSee('data-platform-fallback="1"', false);
    }
}
