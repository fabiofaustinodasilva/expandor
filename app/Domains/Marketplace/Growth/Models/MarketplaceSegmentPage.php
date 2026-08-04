<?php

namespace App\Domains\Marketplace\Growth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MarketplaceSegmentPage extends Model
{
    protected $table = 'marketplace_segment_pages';

    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'description',
        'hero_image',
        'hero_video',
        'features',
        'cta_text',
        'cta_url',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'active' => 'boolean',
        ];
    }

    public function mediaUrl(?string $path): ?string
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
