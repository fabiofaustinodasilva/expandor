<?php

namespace App\Domains\Marketplace\Growth\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceCampaign extends Model
{
    protected $table = 'marketplace_campaigns';

    protected $fillable = [
        'name',
        'source',
        'medium',
        'campaign',
        'investment',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'investment' => 'float',
        ];
    }
}
