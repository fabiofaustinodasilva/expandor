<?php

namespace App\Domains\AI\DTOs;

use App\Domains\AI\Enums\AIContextType;

class AIContextPayload
{
    public function __construct(
        public readonly AIContextType $type,
        public readonly string $body,
        public readonly int $companyId,
    ) {}
}
