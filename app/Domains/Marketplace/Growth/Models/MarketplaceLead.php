<?php

namespace App\Domains\Marketplace\Growth\Models;

use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceLead extends Model
{
    protected $table = 'marketplace_leads';

    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'segment',
        'employees',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'status',
        'notes',
        'session_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => MarketplaceLeadStatus::class,
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(\App\Domains\Marketplace\Models\MarketplaceEvent::class, 'lead_id');
    }
}
