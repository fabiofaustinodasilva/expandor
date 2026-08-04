<?php

namespace App\Domains\Marketplace\Growth\Models;

use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Revenue\Models\MarketplaceLeadScore;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        return $this->hasMany(MarketplaceEvent::class, 'lead_id');
    }

    public function score(): HasOne
    {
        return $this->hasOne(MarketplaceLeadScore::class, 'lead_id');
    }

    public function pipeline(): HasOne
    {
        return $this->hasOne(MarketplaceSalesPipeline::class, 'lead_id');
    }
}
