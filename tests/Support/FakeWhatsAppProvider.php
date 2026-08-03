<?php

namespace Tests\Support;

use App\Domains\Communication\Providers\DTOs\WhatsAppProviderStatus;
use App\Domains\Communication\Providers\DTOs\WhatsAppSendResult;
use App\Domains\Communication\Providers\WhatsAppProviderContract;
use RuntimeException;

class FakeWhatsAppProvider implements WhatsAppProviderContract
{
    /** @var list<array{to: string, body: string, options: array<string, mixed>}> */
    public array $sentMessages = [];

    public bool $shouldFail = false;

    public bool $shouldThrow = false;

    public string $driverName = 'fake';

    public function sendMessage(string $to, string $body, array $options = []): WhatsAppSendResult
    {
        if ($this->shouldThrow) {
            throw new RuntimeException('Simulated provider crash.');
        }

        $this->sentMessages[] = [
            'to' => $to,
            'body' => $body,
            'options' => $options,
        ];

        if ($this->shouldFail) {
            return WhatsAppSendResult::fail('Simulated provider failure.');
        }

        return WhatsAppSendResult::ok('fake-msg-1', ['driver' => $this->driverName]);
    }

    public function sendMedia(string $to, string $mediaUrl, string $caption = '', array $options = []): WhatsAppSendResult
    {
        if ($this->shouldThrow) {
            throw new RuntimeException('Simulated provider crash.');
        }

        if ($this->shouldFail) {
            return WhatsAppSendResult::fail('Simulated media failure.');
        }

        return WhatsAppSendResult::ok('fake-media-1');
    }

    public function getStatus(): WhatsAppProviderStatus
    {
        return new WhatsAppProviderStatus(
            state: 'connected',
            connected: true,
            detail: $this->driverName,
        );
    }
}
