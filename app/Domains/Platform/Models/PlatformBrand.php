<?php

namespace App\Domains\Platform\Models;

use App\Domains\Company\Models\User;
use App\Domains\Media\Services\MediaUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformBrand extends Model
{
    protected $fillable = [
        'name',
        'slogan',
        'logo',
        'logo_small',
        'favicon',
        'colors',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'colors' => 'array',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logoUrl(): ?string
    {
        return app(MediaUploadService::class)->url($this->logo);
    }

    public function logoSmallUrl(): ?string
    {
        return app(MediaUploadService::class)->url($this->logo_small ?: $this->logo);
    }

    public function faviconUrl(): ?string
    {
        return app(MediaUploadService::class)->url($this->favicon ?: $this->logo_small ?: $this->logo);
    }

    public function primaryColor(): string
    {
        return $this->colors['primary'] ?? '#3B82F6';
    }

    public function secondaryColor(): string
    {
        return $this->colors['secondary'] ?? '#171A22';
    }

    public function highlightColor(): string
    {
        return $this->colors['highlight'] ?? '#EF4444';
    }
}
