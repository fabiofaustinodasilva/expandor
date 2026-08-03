<?php

namespace App\Domains\Communication\Events;

use App\Domains\Communication\Models\Message;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Message $message
    ) {}
}
