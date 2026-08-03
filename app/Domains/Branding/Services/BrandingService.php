<?php

namespace App\Domains\Branding\Services;

use App\Domains\Branding\DTOs\BrandPayload;
use App\Domains\Branding\Enums\BrandTheme;
use App\Domains\Branding\Models\Brand;
use App\Domains\Branding\Repositories\BrandRepository;
use App\Domains\Company\Models\Company;
use App\Domains\Media\Enums\MediaCategory;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Platform\Services\PlatformBrandingService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class BrandingService
{
    public function __construct(
        protected BrandRepository $brands,
        protected ThemeService $themes,
        protected TenantContext $tenant,
        protected MediaUploadService $media,
        protected PlatformBrandingService $platformBranding,
    ) {}

    public function defaults(): BrandPayload
    {
        return $this->platformBranding->payload();
    }

    public function forCompany(?Company $company = null): BrandPayload
    {
        $company ??= $this->tenant->company();

        if ($company === null) {
            return $this->defaults();
        }

        $brand = $this->brands->findForCompany($company);

        if ($brand === null) {
            $platform = $this->defaults();

            return new BrandPayload(
                systemName: $platform->systemName,
                displayName: $company->name ?: $platform->displayName,
                slogan: $platform->slogan,
                logoUrl: $platform->logoUrl,
                logoMarkUrl: $platform->logoMarkUrl,
                faviconUrl: $platform->faviconUrl,
                loginImageUrl: null,
                colors: $platform->colors,
                theme: $platform->theme,
                fonts: $platform->fonts,
                supportEmail: $company->email ?: $platform->supportEmail,
                supportPhone: $company->phone ?: $platform->supportPhone,
                socials: $platform->socials,
                customDomain: null,
                customCss: null,
                isCustomized: false,
                companyId: $company->id,
            );
        }

        $payload = BrandPayload::fromBrand($brand);
        $platform = $this->defaults();

        // Empresa sem logo (ou arquivo ausente no disco) herda assets da plataforma.
        return new BrandPayload(
            systemName: $payload->systemName,
            displayName: $payload->displayName,
            slogan: $payload->slogan ?? $platform->slogan,
            logoUrl: $payload->logoUrl ?: $platform->logoUrl,
            logoMarkUrl: $payload->logoMarkUrl ?: $platform->logoMarkUrl,
            faviconUrl: $payload->faviconUrl ?: $platform->faviconUrl,
            loginImageUrl: $payload->loginImageUrl,
            colors: $payload->colors,
            theme: $payload->theme,
            fonts: $payload->fonts,
            supportEmail: $payload->supportEmail ?: $platform->supportEmail,
            supportPhone: $payload->supportPhone ?: $platform->supportPhone,
            socials: $payload->socials,
            customDomain: $payload->customDomain,
            customCss: $payload->customCss,
            isCustomized: true,
            companyId: $payload->companyId,
        );
    }

    public function resolveForRequest(?Request $request = null): BrandPayload
    {
        $request ??= request();

        $host = $this->normalizeHost($request->getHost());

        if ($host !== null && ! $this->isAppHost($host)) {
            $byDomain = $this->brands->findByCustomDomain($host);

            if ($byDomain !== null) {
                return BrandPayload::fromBrand($byDomain);
            }
        }

        $company = $this->tenant->company() ?? $request->user()?->company;

        // Antes do login (sem empresa): branding da plataforma Expandor.
        if ($company === null || $request->user() === null) {
            return $this->defaults();
        }

        // Depois do login: branding do tenant (com fallback de plataforma).
        return $this->forCompany($company);
    }

    public function resolveCompanyByHost(?string $host): ?Company
    {
        $normalized = $this->normalizeHost($host);

        if ($normalized === null || $this->isAppHost($normalized)) {
            return null;
        }

        $brand = $this->brands->findByCustomDomain($normalized);

        return $brand?->company;
    }

    public function modelForCompany(?Company $company = null): ?Brand
    {
        $company ??= $this->tenant->company();

        if ($company === null) {
            return null;
        }

        return $this->brands->findForCompany($company);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $files
     * @param  array<string, bool>  $removals
     */
    public function store(Company $company, array $data, array $files = [], array $removals = []): Brand
    {
        return $this->persist($company, $data, $files, $removals);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $files
     * @param  array<string, bool>  $removals
     */
    public function update(Company $company, array $data, array $files = [], array $removals = []): Brand
    {
        return $this->persist($company, $data, $files, $removals);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $files
     * @param  array<string, bool>  $removals
     */
    protected function persist(Company $company, array $data, array $files = [], array $removals = []): Brand
    {
        $existing = $this->brands->findForCompany($company);

        $attributes = [
            'system_name' => $data['system_name'] ?? null,
            'display_name' => $data['display_name'] ?? null,
            'slogan' => isset($data['slogan']) ? (trim((string) $data['slogan']) ?: null) : ($existing?->slogan),
            'colors' => $this->themes->normalizeColors($data['colors'] ?? []),
            'theme' => BrandTheme::tryFrom((string) ($data['theme'] ?? BrandTheme::Dark->value))?->value
                ?? BrandTheme::Dark->value,
            'fonts' => $this->themes->normalizeFonts($data['fonts'] ?? []),
            'support_email' => $data['support_email'] ?? null,
            'support_phone' => $data['support_phone'] ?? null,
            'socials' => $this->themes->normalizeSocials($data['socials'] ?? []),
            'custom_domain' => $this->normalizeHost($data['custom_domain'] ?? null),
            'custom_css' => $data['custom_css'] ?? null,
        ];

        $map = [
            'logo' => MediaPurpose::Logo,
            'logo_mark' => MediaPurpose::LogoMark,
            'favicon' => MediaPurpose::Favicon,
            'login_image' => MediaPurpose::LoginImage,
        ];

        foreach ($map as $field => $purpose) {
            $file = $files[$field] ?? null;
            $remove = (bool) ($removals[$field] ?? false);

            if ($file instanceof UploadedFile) {
                $result = $this->media->store(
                    $file,
                    (int) $company->id,
                    MediaCategory::Branding,
                    $purpose,
                    $existing?->{$field},
                    field: $field,
                );
                $attributes[$field] = $result->path;
            } elseif ($remove) {
                $this->media->delete($existing?->{$field});
                $attributes[$field] = null;
            } elseif ($existing?->{$field}) {
                $attributes[$field] = $existing->{$field};
            }
        }

        return $this->brands->upsertForCompany($company, $attributes);
    }

    public function name(?BrandPayload $brand = null): string
    {
        return ($brand ?? $this->resolveForRequest())->name();
    }

    public function logo(?BrandPayload $brand = null): ?string
    {
        return ($brand ?? $this->resolveForRequest())->logo();
    }

    public function logoMark(?BrandPayload $brand = null): ?string
    {
        return ($brand ?? $this->resolveForRequest())->logoMark();
    }

    public function favicon(?BrandPayload $brand = null): ?string
    {
        return ($brand ?? $this->resolveForRequest())->favicon();
    }

    /**
     * @return array<string, string>
     */
    public function colors(?BrandPayload $brand = null): array
    {
        return ($brand ?? $this->resolveForRequest())->colors;
    }

    public function primaryColor(?BrandPayload $brand = null): string
    {
        return ($brand ?? $this->resolveForRequest())->primaryColor();
    }

    protected function normalizeHost(?string $host): ?string
    {
        if ($host === null) {
            return null;
        }

        $host = strtolower(trim($host));
        $host = preg_replace('#^https?://#', '', $host) ?? $host;
        $host = rtrim($host, '/');
        $host = explode(':', $host)[0];

        return $host !== '' ? $host : null;
    }

    protected function isAppHost(string $host): bool
    {
        $appHost = $this->normalizeHost(parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($appHost === null) {
            return in_array($host, ['localhost', '127.0.0.1'], true);
        }

        return $host === $appHost || in_array($host, ['localhost', '127.0.0.1'], true);
    }
}
