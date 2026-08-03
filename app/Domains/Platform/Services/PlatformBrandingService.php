<?php

namespace App\Domains\Platform\Services;

use App\Domains\Branding\DTOs\BrandPayload;
use App\Domains\Branding\Enums\BrandTheme;
use App\Domains\Branding\Services\ThemeService;
use App\Domains\Company\Models\User;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Platform\Models\PlatformBrand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PlatformBrandingService
{
    public function __construct(
        protected MediaUploadService $media,
        protected ThemeService $themes,
    ) {}

    public function current(): ?PlatformBrand
    {
        return PlatformBrand::query()->latest('id')->first();
    }

    public function payload(): BrandPayload
    {
        $row = $this->current();
        $defaults = BrandPayload::defaults();

        if ($row === null) {
            return $defaults;
        }

        $colors = $this->themes->normalizeColors(array_merge($defaults->colors, [
            'primary' => $row->colors['primary'] ?? $defaults->primaryColor(),
            'secondary' => $row->colors['secondary'] ?? $defaults->secondaryColor(),
            'highlight' => $row->colors['highlight'] ?? $defaults->highlightColor(),
            'accent' => $row->colors['primary'] ?? $defaults->primaryColor(),
            'accent_2' => $row->colors['highlight'] ?? $defaults->highlightColor(),
            'bg_elevated' => $row->colors['secondary'] ?? $defaults->secondaryColor(),
        ]));

        $name = trim((string) $row->name) !== '' ? $row->name : $defaults->displayName;
        $slogan = trim((string) ($row->slogan ?? ''));

        return new BrandPayload(
            systemName: $name,
            displayName: $name,
            slogan: $slogan !== '' ? $slogan : null,
            logoUrl: $row->logoUrl(),
            logoMarkUrl: $row->logoSmallUrl(),
            faviconUrl: $row->faviconUrl(),
            loginImageUrl: null,
            colors: $colors,
            theme: BrandTheme::Dark,
            fonts: $defaults->fonts,
            supportEmail: $defaults->supportEmail,
            supportPhone: $defaults->supportPhone,
            socials: $defaults->socials,
            customDomain: null,
            customCss: null,
            isCustomized: true,
            companyId: null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $files
     * @param  array<string, bool>  $removals
     */
    public function update(array $data, array $files = [], array $removals = [], ?User $actor = null): PlatformBrand
    {
        return DB::transaction(function () use ($data, $files, $removals, $actor) {
            $row = $this->current() ?? new PlatformBrand([
                'name' => 'Expandor',
            ]);

            $colors = $this->themes->normalizeColors([
                'primary' => $data['primary_color'] ?? $row->colors['primary'] ?? null,
                'secondary' => $data['secondary_color'] ?? $row->colors['secondary'] ?? null,
                'highlight' => $data['highlight_color'] ?? $row->colors['highlight'] ?? null,
            ]);

            $row->name = trim((string) ($data['name'] ?? $row->name ?: 'Expandor')) ?: 'Expandor';
            $slogan = trim((string) ($data['slogan'] ?? $row->slogan ?? ''));
            $row->slogan = $slogan !== '' ? $slogan : null;
            $row->colors = [
                'primary' => $colors['primary'],
                'secondary' => $colors['secondary'],
                'highlight' => $colors['highlight'],
            ];
            $row->updated_by = $actor?->id;

            $map = [
                'logo' => MediaPurpose::Logo,
                'logo_small' => MediaPurpose::LogoMark,
                'favicon' => MediaPurpose::Favicon,
            ];

            foreach ($map as $field => $purpose) {
                $file = $files[$field] ?? null;
                $remove = (bool) ($removals[$field] ?? false);

                if ($file instanceof UploadedFile) {
                    $previous = $row->{$field};
                    $result = $this->media->storePlatform(
                        $file,
                        $purpose,
                        $previous,
                        field: $field,
                    );
                    $row->{$field} = $result->path;

                    // Se o favicon apontava para o arquivo antigo (logo/logo_small), limpa para recalcular.
                    if ($field !== 'favicon' && $row->favicon && $previous && $row->favicon === $previous) {
                        $row->favicon = null;
                    }
                } elseif ($remove) {
                    $previous = $row->{$field};
                    $this->media->delete($previous);
                    $row->{$field} = null;

                    if ($field !== 'favicon' && $row->favicon && $previous && $row->favicon === $previous) {
                        $row->favicon = null;
                    }
                }
            }

            // Favicon fallback: logo reduzida (ou logo) se favicon vazio.
            if (! $row->favicon && $row->logo_small) {
                $row->favicon = $row->logo_small;
            }

            $row->save();

            return $row->refresh();
        });
    }
}
