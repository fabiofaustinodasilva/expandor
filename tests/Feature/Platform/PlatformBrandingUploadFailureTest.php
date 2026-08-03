<?php

namespace Tests\Feature\Platform;

use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Platform\Models\PlatformBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class PlatformBrandingUploadFailureTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_invalid_php_upload_shows_friendly_message_not_validation_key(): void
    {
        $owner = $this->makePlatformAdmin();

        $tmp = tempnam(sys_get_temp_dir(), 'exp');
        file_put_contents($tmp, 'x');

        $broken = new UploadedFile(
            path: $tmp,
            originalName: 'logo.png',
            mimeType: 'image/png',
            error: UPLOAD_ERR_INI_SIZE,
            test: true,
        );

        Log::spy();

        $this->actingAs($owner)
            ->from(route('platform.branding.edit'))
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#171A22',
                'highlight_color' => '#EF4444',
                'logo' => $broken,
            ])
            ->assertRedirect(route('platform.branding.edit'))
            ->assertSessionHasErrors('logo');

        $errors = session('errors');
        $this->assertNotNull($errors);
        $message = $errors->first('logo');

        $this->assertStringNotContainsString('validation.uploaded', $message);
        $this->assertStringContainsString('Não foi possível enviar a imagem', $message);
        $this->assertNull(PlatformBrand::query()->first());

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $event, array $context): bool => $event === 'media.upload_attempt'
                && ($context['field'] ?? null) === 'logo'
                && ($context['error'] ?? null) === UPLOAD_ERR_INI_SIZE)
            ->atLeast()
            ->once();
    }

    public function test_partial_upload_error_mentions_corrupted_transfer(): void
    {
        $owner = $this->makePlatformAdmin();

        $tmp = tempnam(sys_get_temp_dir(), 'exp');
        file_put_contents($tmp, 'partial');

        $partial = new UploadedFile(
            path: $tmp,
            originalName: 'logo.png',
            mimeType: 'image/png',
            error: UPLOAD_ERR_PARTIAL,
            test: true,
        );

        $this->actingAs($owner)
            ->from(route('platform.branding.edit'))
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#171A22',
                'highlight_color' => '#EF4444',
                'logo' => $partial,
            ])
            ->assertRedirect(route('platform.branding.edit'))
            ->assertSessionHasErrors(['logo' => 'Upload incompleto (arquivo corrompido na transferência). Tente novamente.']);
    }

    public function test_invalid_mime_returns_format_message(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->from(route('platform.branding.edit'))
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#171A22',
                'highlight_color' => '#EF4444',
                'logo' => UploadedFile::fake()->create('malware.exe', 20, 'application/x-msdownload'),
            ])
            ->assertRedirect(route('platform.branding.edit'))
            ->assertSessionHasErrors('logo');

        $message = session('errors')->first('logo');
        $this->assertStringNotContainsString('validation.', $message);
        $this->assertTrue(
            str_contains($message, 'Formato')
            || str_contains($message, 'MIME')
            || str_contains($message, 'Não foi possível enviar')
            || str_contains($message, 'bloqueado')
            || str_contains($message, 'tipo'),
            "Mensagem inesperada: {$message}"
        );
    }

    public function test_friendly_upload_error_helper_covers_php_limits(): void
    {
        $media = app(MediaUploadService::class);

        $iniSize = new UploadedFile(
            path: sys_get_temp_dir().DIRECTORY_SEPARATOR.'x.png',
            originalName: 'x.png',
            mimeType: 'image/png',
            error: UPLOAD_ERR_INI_SIZE,
            test: true,
        );

        $this->assertStringContainsString('limite do servidor', $media->friendlyUploadError($iniSize));
        $this->assertStringContainsString('Não foi possível enviar a imagem', $media->friendlyUploadError($iniSize));
    }

    public function test_successful_png_upload_still_works_after_fix(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#171A22',
                'highlight_color' => '#EF4444',
                'logo' => UploadedFile::fake()->image('ok.png', 120, 40),
            ])
            ->assertRedirect(route('platform.branding.edit'))
            ->assertSessionHasNoErrors();

        $row = PlatformBrand::query()->first();
        $this->assertNotNull($row);
        $this->assertStringStartsWith('platform/branding/', (string) $row->logo);
    }
}
