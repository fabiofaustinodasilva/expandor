<?php

namespace App\Domains\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MarketplaceTestimonial extends Model
{
    protected $fillable = [
        'name',
        'company',
        'avatar',
        'text',
        'rating',
        'active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function avatarUrl(): ?string
    {
        if (! filled($this->avatar)) {
            return null;
        }
        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }
}
