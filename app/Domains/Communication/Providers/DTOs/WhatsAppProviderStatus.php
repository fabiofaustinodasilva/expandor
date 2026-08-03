<?php

namespace App\Domains\Communication\Providers\DTOs;

readonly class WhatsAppProviderStatus
{
    public function __construct(
        public string $state,
        public bool $connected,
        public ?string $detail = null,
        public array $raw = [],
    ) {}
}
