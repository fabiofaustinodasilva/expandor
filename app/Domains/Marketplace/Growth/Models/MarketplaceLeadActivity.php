<?php

namespace App\Domains\Marketplace\Growth\Models;

use App\Domains\Company\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceLeadActivity extends Model
{
    protected $table = 'marketplace_lead_activities';

    protected $fillable = [
        'lead_id',
        'type',
        'label',
        'detail',
        'user_id',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(MarketplaceLead::class, 'lead_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
