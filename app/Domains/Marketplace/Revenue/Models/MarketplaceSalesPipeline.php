<?php

namespace App\Domains\Marketplace\Revenue\Models;

use App\Domains\Company\Models\User;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSalesPipeline extends Model
{
    protected $table = 'marketplace_sales_pipeline';

    protected $fillable = [
        'lead_id',
        'stage',
        'assigned_user_id',
        'notes',
        'last_contact_at',
        'demo_scheduled_at',
        'next_action_at',
        'next_action_label',
    ];

    protected function casts(): array
    {
        return [
            'stage' => PipelineStage::class,
            'last_contact_at' => 'datetime',
            'demo_scheduled_at' => 'datetime',
            'next_action_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(MarketplaceLead::class, 'lead_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
