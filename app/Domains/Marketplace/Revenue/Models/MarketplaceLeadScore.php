<?php

namespace App\Domains\Marketplace\Revenue\Models;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Revenue\Enums\LeadTemperature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceLeadScore extends Model
{
    protected $table = 'marketplace_lead_scores';

    protected $fillable = [
        'lead_id',
        'score',
        'visited_pricing',
        'watched_video',
        'clicked_whatsapp',
        'used_roi_calculator',
        'requested_demo',
        'started_trial',
        'temperature',
        'hot_detected_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'visited_pricing' => 'boolean',
            'watched_video' => 'boolean',
            'clicked_whatsapp' => 'boolean',
            'used_roi_calculator' => 'boolean',
            'requested_demo' => 'boolean',
            'started_trial' => 'boolean',
            'temperature' => LeadTemperature::class,
            'hot_detected_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(MarketplaceLead::class, 'lead_id');
    }

    public function isHot(): bool
    {
        return $this->temperature === LeadTemperature::Hot || $this->score >= 71;
    }
}
