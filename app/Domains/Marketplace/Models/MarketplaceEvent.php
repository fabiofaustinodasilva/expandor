<?php

namespace App\Domains\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event',
        'session_id',
        'company_id',
        'lead_id',
        'url',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'device',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Marketplace\Growth\Models\MarketplaceLead::class, 'lead_id');
    }
}
