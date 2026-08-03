<?php

namespace App\Domains\Payments\Models;

use App\Domains\Payments\Enums\WebhookEventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway',
        'event_id',
        'event_type',
        'payload',
        'status',
        'processed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => WebhookEventStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\WebhookEventFactory::new();
    }
}
