<?php

namespace App\Domains\Marketplace\Growth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceLeadNotification extends Model
{
    public const TYPE_NEW_DEMO = 'new_demo_lead';

    public const TYPE_TEST = 'commercial_alert_test';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'marketplace_lead_notifications';

    protected $fillable = [
        'lead_id',
        'type',
        'channel',
        'status',
        'to_phone',
        'body',
        'error',
        'provider_message_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(MarketplaceLead::class, 'lead_id');
    }
}
