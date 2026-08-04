<?php

namespace App\Domains\Marketplace\Growth\Models;

use App\Domains\Company\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceTrialMilestone extends Model
{
    protected $table = 'marketplace_trial_milestones';

    protected $fillable = [
        'company_id',
        'event',
        'occurred_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
