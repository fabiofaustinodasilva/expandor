<?php

namespace App\Domains\Communication\Listeners;

use App\Domains\Communication\Events\MessageRegistered;
use Illuminate\Support\Facades\Log;

class LogMessageRegistered
{
    public function handle(MessageRegistered $event): void
    {
        Log::info('communication.message_registered', [
            'message_id' => $event->message->id,
            'company_id' => $event->message->company_id,
            'resident_id' => $event->message->resident_id,
            'direction' => $event->message->direction?->value,
            'status' => $event->message->status?->value,
        ]);
    }
}
