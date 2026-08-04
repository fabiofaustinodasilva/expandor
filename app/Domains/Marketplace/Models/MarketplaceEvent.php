<?php

namespace App\Domains\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event',
        'ip_hash',
        'user_agent',
        'origin',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
