<?php

namespace App\Domains\Branding\DTOs;

use App\Domains\Branding\Enums\BrandTheme;
use App\Domains\Branding\Models\Brand;

readonly class BrandPayload
{
    /**
     * @param  array<string, string>  $colors
     * @param  array<string, string|null>  $fonts
     * @param  array<string, string|null>  $socials
     */
    public function __construct(
        public string $systemName,
        public string $displayName,
        public ?string $slogan,
        public ?string $logoUrl,
        public ?string $logoMarkUrl,
        public ?string $faviconUrl,
        public ?string $loginImageUrl,
        public array $colors,
        public BrandTheme $theme,
        public array $fonts,
        public ?string $supportEmail,
        public ?string $supportPhone,
        public array $socials,
        public ?string $customDomain,
        public ?string $customCss,
        public bool $isCustomized,
        public ?int $companyId = null,
    ) {}

    public static function defaults(): self
    {
        return new self(
            systemName: (string) config('app.name', 'Expandor'),
            displayName: 'Expandor',
            slogan: null,
            logoUrl: null,
            logoMarkUrl: null,
            faviconUrl: null,
            loginImageUrl: null,
            colors: self::defaultColors(),
            theme: BrandTheme::Dark,
            fonts: self::defaultFonts(),
            supportEmail: null,
            supportPhone: null,
            socials: self::defaultSocials(),
            customDomain: null,
            customCss: null,
            isCustomized: false,
            companyId: null,
        );
    }

    public static function fromBrand(Brand $brand): self
    {
        $defaults = self::defaults();
        $slogan = trim((string) ($brand->slogan ?? ''));

        return new self(
            systemName: $brand->system_name ?: $defaults->systemName,
            displayName: $brand->display_name ?: $defaults->displayName,
            slogan: $slogan !== '' ? $slogan : null,
            logoUrl: $brand->logoUrl(),
            logoMarkUrl: $brand->logoMarkUrl(),
            faviconUrl: $brand->faviconUrl(),
            loginImageUrl: $brand->loginImageUrl(),
            colors: array_merge($defaults->colors, $brand->colors ?? []),
            theme: $brand->theme ?? BrandTheme::Dark,
            fonts: array_merge($defaults->fonts, $brand->fonts ?? []),
            supportEmail: $brand->support_email,
            supportPhone: $brand->support_phone,
            socials: array_merge($defaults->socials, $brand->socials ?? []),
            customDomain: $brand->custom_domain,
            customCss: $brand->custom_css,
            isCustomized: true,
            companyId: $brand->company_id,
        );
    }

    public function name(): string
    {
        return $this->displayName !== '' ? $this->displayName : $this->systemName;
    }

    public function sloganText(): ?string
    {
        $slogan = trim((string) ($this->slogan ?? ''));

        return $slogan !== '' ? $slogan : null;
    }

    public function logo(): ?string
    {
        return $this->logoUrl;
    }

    public function logoMark(): ?string
    {
        return $this->logoMarkUrl ?: $this->logoUrl;
    }

    public function favicon(): ?string
    {
        return $this->faviconUrl;
    }

    public function primaryColor(): string
    {
        return $this->colors['primary'] ?? $this->colors['accent'] ?? self::defaultColors()['accent'];
    }

    public function secondaryColor(): string
    {
        return $this->colors['secondary'] ?? $this->colors['bg_elevated'] ?? self::defaultColors()['bg_elevated'];
    }

    public function highlightColor(): string
    {
        return $this->colors['highlight'] ?? $this->colors['accent_2'] ?? self::defaultColors()['accent_2'];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultColors(): array
    {
        return [
            'bg' => '#0F1117',
            'bg_elevated' => '#171A22',
            'bg_soft' => '#1E2330',
            'border' => '#2A3142',
            'text' => '#F3F5F9',
            'muted' => '#9AA3B5',
            'accent' => '#3B82F6',
            'accent_2' => '#EF4444',
            'primary' => '#3B82F6',
            'secondary' => '#171A22',
            'highlight' => '#EF4444',
            'success' => '#22C55E',
            'warning' => '#F59E0B',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public static function defaultFonts(): array
    {
        return [
            'family' => '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif',
            'heading' => null,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public static function defaultSocials(): array
    {
        return [
            'facebook' => null,
            'instagram' => null,
            'linkedin' => null,
            'twitter' => null,
            'youtube' => null,
            'whatsapp' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'system_name' => $this->systemName,
            'display_name' => $this->displayName,
            'slogan' => $this->sloganText(),
            'name' => $this->name(),
            'logo_url' => $this->logoUrl,
            'logo_mark_url' => $this->logoMark(),
            'favicon_url' => $this->faviconUrl,
            'login_image_url' => $this->loginImageUrl,
            'colors' => $this->colors,
            'primary_color' => $this->primaryColor(),
            'secondary_color' => $this->secondaryColor(),
            'highlight_color' => $this->highlightColor(),
            'theme' => $this->theme->value,
            'fonts' => $this->fonts,
            'support_email' => $this->supportEmail,
            'support_phone' => $this->supportPhone,
            'socials' => $this->socials,
            'custom_domain' => $this->customDomain,
            'custom_css' => $this->customCss,
            'is_customized' => $this->isCustomized,
        ];
    }
}
