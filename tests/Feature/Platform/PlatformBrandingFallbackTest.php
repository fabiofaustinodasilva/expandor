<?php

namespace Tests\Feature\Platform;

use App\Domains\Platform\Models\PlatformBrand;
use App\Domains\Platform\Services\PlatformBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Validação final Sprint 5.4.3 — fallback e login com branding Expandor.
 */
class PlatformBrandingFallbackTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_login_without_platform_brand_uses_expandor_fallback(): void
    {
        $this->assertNull(PlatformBrand::query()->first());

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Login — Expandor', false)
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('Acesse sua conta', false)
            ->assertDontSee('rel="icon"', false);
    }

    public function test_login_with_platform_logo_favicon_and_title(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor Cloud',
            'primary_color' => '#2563EB',
            'secondary_color' => '#0F172A',
            'highlight_color' => '#F97316',
        ], [
            'logo' => UploadedFile::fake()->image('logo.png', 180, 60),
            'logo_small' => UploadedFile::fake()->image('mark.png', 48, 48),
            'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
        ]);

        $payload = app(PlatformBrandingService::class)->payload();

        $this->assertNotNull($payload->logoUrl);
        $this->assertNotNull($payload->faviconUrl);
        $this->assertStringContainsString('platform/branding/', $payload->logoUrl);
        $this->assertStringContainsString('/storage/', $payload->faviconUrl);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Login — Expandor Cloud', false)
            ->assertSee('Bem-vindo ao Expandor Cloud', false)
            ->assertSee($payload->logoUrl, false)
            ->assertSee('rel="icon"', false)
            ->assertSee($payload->faviconUrl, false)
            ->assertDontSee('companies/');
    }

    public function test_favicon_falls_back_to_logo_small_when_favicon_empty(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#171A22',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('logo.png', 160, 60),
            'logo_small' => UploadedFile::fake()->image('mark.png', 40, 40),
        ]);

        $row = PlatformBrand::query()->first();
        $this->assertNotNull($row);
        $this->assertSame($row->logo_small, $row->favicon);

        $payload = app(PlatformBrandingService::class)->payload();
        $this->assertSame($row->faviconUrl(), $payload->faviconUrl);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="icon"', false)
            ->assertSee($payload->faviconUrl, false);
    }

    public function test_platform_payload_reads_fresh_after_update_without_cache(): void
    {
        $service = app(PlatformBrandingService::class);

        $before = $service->payload();
        $this->assertSame('Expandor', $before->name());
        $this->assertNull($before->logoUrl);

        $service->update([
            'name' => 'Expandor Atualizado',
            'primary_color' => '#111827',
            'secondary_color' => '#1F2937',
            'highlight_color' => '#DC2626',
        ], [
            'logo' => UploadedFile::fake()->image('novo.png', 120, 40),
        ]);

        // Sem cache de branding: leitura imediata do banco.
        $after = app(PlatformBrandingService::class)->payload();
        $this->assertSame('Expandor Atualizado', $after->name());
        $this->assertNotNull($after->logoUrl);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Login — Expandor Atualizado', false)
            ->assertSee($after->logoUrl, false);
    }
}
