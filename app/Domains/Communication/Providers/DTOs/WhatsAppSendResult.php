<?php

namespace App\Domains\Communication\Providers\DTOs;

readonly class WhatsAppSendResult
{
    public function __construct(
        public bool $success,
        public ?string $providerMessageId = null,
        public ?string $error = null,
        public array $raw = [],
    ) {}

    public static function ok(?string $providerMessageId = null, array $raw = []): self
    {
        return new self(true, $providerMessageId, null, $raw);
    }

    public static function fail(string $error, array $raw = []): self
    {
        return new self(false, null, $error, $raw);
    }
}
