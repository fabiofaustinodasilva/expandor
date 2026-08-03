<?php

namespace App\Domains\Communication\Providers;

use App\Domains\Communication\Providers\DTOs\WhatsAppProviderStatus;
use App\Domains\Communication\Providers\DTOs\WhatsAppSendResult;

interface WhatsAppProviderContract
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function sendMessage(string $to, string $body, array $options = []): WhatsAppSendResult;

    /**
     * @param  array<string, mixed>  $options
     */
    public function sendMedia(string $to, string $mediaUrl, string $caption = '', array $options = []): WhatsAppSendResult;

    public function getStatus(): WhatsAppProviderStatus;
}
