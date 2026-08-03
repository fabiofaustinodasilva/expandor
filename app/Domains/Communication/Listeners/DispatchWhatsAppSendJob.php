<?php

namespace App\Domains\Communication\Listeners;

use App\Domains\Communication\Events\MessageQueuedForDelivery;
use App\Domains\Communication\Jobs\SendWhatsAppMessageJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchWhatsAppSendJob implements ShouldQueue
{
    public function handle(MessageQueuedForDelivery $event): void
    {
        SendWhatsAppMessageJob::dispatch($event->message->id);
    }
}
