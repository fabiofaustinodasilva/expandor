<?php

namespace App\Domains\Marketplace\Models;

use App\Domains\Marketplace\Enums\MarketplaceSectionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MarketplaceSection extends Model
{
    protected $fillable = [
        'type',
        'title',
        'subtitle',
        'description',
        'image',
        'video',
        'button_text',
        'button_url',
        'order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'type' => MarketplaceSectionType::class,
            'order' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function imageUrl(): ?string
    {
        return $this->publicUrl($this->image);
    }

    public function videoUrl(): ?string
    {
        return $this->publicUrl($this->video);
    }

    protected function publicUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
