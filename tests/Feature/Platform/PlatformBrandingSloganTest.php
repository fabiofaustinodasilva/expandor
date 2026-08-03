<?php

namespace Tests\Feature\Platform;

use App\Domains\Platform\Models\PlatformBrand;
use App\Domains\Platform\Services\PlatformBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Garante coluna slogan + persistência Platform Branding (Sprint 5.5.1).
 */
class PlatformBrandingSloganTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_migration_adds_slogan_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('platform_brands', 'slogan'));
        $this->assertTrue(Schema::hasColumn('brands', 'slogan'));
    }

    public function test_owner_can_save_and_retrieve_slogan(): void
    {
        $owner = $this->makePlatformAdmin();
        $slogan = 'MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA';

        $this->actingAs($owner)
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'slogan' => $slogan,
                'primary_color' => '#3B82F6',
                'secondary_color' => '#0F172A',
                'highlight_color' => '#EF4444',
            ])
            ->assertRedirect(route('platform.branding.edit'));

        $row = PlatformBrand::query()->first();
        $this->assertNotNull($row);
        $this->assertSame('Expandor', $row->name);
        $this->assertSame($slogan, $row->slogan);

        $payload = app(PlatformBrandingService::class)->payload();
        $this->assertSame('Expandor', $payload->name());
        $this->assertSame($slogan, $payload->sloganText());
    }

    public function test_login_renders_slogan_without_duplicating_name(): void
    {
        $slogan = 'MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA';

        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'slogan' => $slogan,
            'primary_color' => '#2563EB',
            'secondary_color' => '#111827',
            'highlight_color' => '#F97316',
        ], [
            'logo' => UploadedFile::fake()->image('logo.png', 180, 60),
        ]);

        $html = $this->get(route('login'))
            ->assertOk()
            ->assertSee($slogan, false)
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertDontSee('Bem-vindo ao '.$slogan, false)
            ->getContent();

        $this->assertSame(1, substr_count($html, $slogan));
        $this->assertSame(1, substr_count($html, 'Bem-vindo ao Expandor'));
        $this->assertStringContainsString('data-platform-slogan="1"', $html);
    }

    public function test_empty_slogan_and_expandor_fallback_still_work(): void
    {
        $this->assertNull(PlatformBrand::query()->first());

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertDontSee('data-platform-slogan="1"', false);

        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'slogan' => '',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#171A22',
            'highlight_color' => '#EF4444',
        ]);

        $this->assertNull(PlatformBrand::query()->value('slogan'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertDontSee('data-platform-slogan="1"', false);
    }
}
