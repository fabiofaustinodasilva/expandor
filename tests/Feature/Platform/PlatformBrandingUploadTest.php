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
 * Validação final Sprint 5.4.3 — upload, replace e paths platform/branding.
 */
class PlatformBrandingUploadTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_owner_can_upload_png_jpg_and_webp_logos(): void
    {
        $owner = $this->makePlatformAdmin();

        foreach (['logo.png', 'logo.jpg', 'logo.webp'] as $filename) {
            PlatformBrand::query()->delete();

            $this->actingAs($owner)
                ->put(route('platform.branding.update'), [
                    'name' => 'Expandor',
                    'primary_color' => '#3B82F6',
                    'secondary_color' => '#171A22',
                    'highlight_color' => '#EF4444',
                    'logo' => UploadedFile::fake()->image($filename, 160, 60),
                ])
                ->assertRedirect(route('platform.branding.edit'));

            $row = PlatformBrand::query()->first();
            $this->assertNotNull($row, "Falha no upload de {$filename}");
            $this->assertStringStartsWith('platform/branding/', (string) $row->logo);
            $this->assertStringNotContainsString('companies/', (string) $row->logo);
            $this->assertTrue(Storage::disk('public')->exists($row->logo));
        }
    }

    public function test_owner_can_replace_and_remove_platform_logo(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#171A22',
                'highlight_color' => '#EF4444',
                'logo' => UploadedFile::fake()->image('primeira.png', 160, 60),
                'logo_small' => UploadedFile::fake()->image('mark.png', 48, 48),
            ])
            ->assertRedirect(route('platform.branding.edit'));

        $first = PlatformBrand::query()->first();
        $this->assertNotNull($first);
        $oldLogo = $first->logo;
        $this->assertTrue(Storage::disk('public')->exists($oldLogo));

        $this->actingAs($owner)
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#171A22',
                'highlight_color' => '#EF4444',
                'logo' => UploadedFile::fake()->image('segunda.png', 180, 70),
            ])
            ->assertRedirect(route('platform.branding.edit'));

        $replaced = PlatformBrand::query()->first();
        $this->assertNotNull($replaced);
        $this->assertNotSame($oldLogo, $replaced->logo);
        $this->assertStringStartsWith('platform/branding/', (string) $replaced->logo);
        $this->assertTrue(Storage::disk('public')->exists($replaced->logo));
        $this->assertFalse(Storage::disk('public')->exists($oldLogo));

        $this->actingAs($owner)
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#171A22',
                'highlight_color' => '#EF4444',
                'remove_logo' => '1',
            ])
            ->assertRedirect(route('platform.branding.edit'));

        $cleared = PlatformBrand::query()->first();
        $this->assertNotNull($cleared);
        $this->assertNull($cleared->logo);
        $this->assertFalse(Storage::disk('public')->exists($replaced->logo));
    }

    public function test_platform_assets_never_land_in_tenant_branding_path(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#171A22',
                'highlight_color' => '#EF4444',
                'logo' => UploadedFile::fake()->image('platform.png', 160, 60),
                'logo_small' => UploadedFile::fake()->image('platform-mark.png', 48, 48),
                'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
            ])
            ->assertRedirect(route('platform.branding.edit'));

        $row = PlatformBrand::query()->first();
        $this->assertNotNull($row);

        foreach ([$row->logo, $row->logo_small, $row->favicon] as $path) {
            $this->assertNotNull($path);
            $this->assertStringStartsWith('platform/branding/', $path);
            $this->assertStringNotContainsString('companies/', $path);
        }

        $files = Storage::disk('public')->allFiles('platform/branding');
        $this->assertNotEmpty($files);
        $this->assertSame([], Storage::disk('public')->allFiles('companies'));
    }

    public function test_service_store_platform_keeps_paths_isolated(): void
    {
        $result = app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#171A22',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('svc.png', 100, 40),
        ]);

        $this->assertStringStartsWith('platform/branding/', (string) $result->logo);
        $this->assertDatabaseHas('platform_brands', [
            'id' => $result->id,
            'name' => 'Expandor',
        ]);
    }
}
