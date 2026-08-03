<?php

namespace App\Domains\Branding\Models;

use App\Domains\Branding\Enums\BrandTheme;
use App\Domains\Company\Models\Company;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Brand extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'system_name',
        'display_name',
        'slogan',
        'logo',
        'logo_mark',
        'favicon',
        'login_image',
        'colors',
        'theme',
        'fonts',
        'support_email',
        'support_phone',
        'socials',
        'custom_domain',
        'custom_css',
    ];

    protected function casts(): array
    {
        return [
            'colors' => 'array',
            'fonts' => 'array',
            'socials' => 'array',
            'theme' => BrandTheme::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function logoUrl(): ?string
    {
        return $this->publicUrl($this->logo);
    }

    public function logoMarkUrl(): ?string
    {
        return $this->publicUrl($this->logo_mark) ?: $this->logoUrl();
    }

    public function faviconUrl(): ?string
    {
        return $this->publicUrl($this->favicon);
    }

    public function loginImageUrl(): ?string
    {
        return $this->publicUrl($this->login_image);
    }

    protected function publicUrl(?string $path): ?string
    {
        return app(\App\Domains\Media\Services\MediaUploadService::class)->url($path);
    }
}
