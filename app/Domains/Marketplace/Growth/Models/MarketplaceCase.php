<?php

namespace App\Domains\Marketplace\Growth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MarketplaceCase extends Model
{
    protected $table = 'marketplace_cases';

    protected $fillable = [
        'company_name',
        'segment',
        'challenge',
        'solution',
        'result',
        'image',
        'video',
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

    public function imageUrl(): ?string
    {
        if (! filled($this->image)) {
            return null;
        }
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        return Storage::disk('public')->url($this->image);
    }
}
