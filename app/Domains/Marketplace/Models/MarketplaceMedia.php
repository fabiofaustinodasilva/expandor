<?php

namespace App\Domains\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MarketplaceMedia extends Model
{
    protected $table = 'marketplace_media';

    protected $fillable = [
        'type',
        'title',
        'caption',
        'path',
        'external_url',
        'thumbnail',
        'order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function displayUrl(): ?string
    {
        if (filled($this->external_url)) {
            return $this->external_url;
        }
        if (! filled($this->path)) {
            return null;
        }
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }

        return Storage::disk('public')->url($this->path);
    }

    public function thumbnailUrl(): ?string
    {
        if (! filled($this->thumbnail)) {
            return $this->type === 'image' ? $this->displayUrl() : null;
        }
        if (str_starts_with($this->thumbnail, 'http')) {
            return $this->thumbnail;
        }

        return Storage::disk('public')->url($this->thumbnail);
    }
}
