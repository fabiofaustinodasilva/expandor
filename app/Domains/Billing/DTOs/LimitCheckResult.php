<?php

namespace App\Domains\Billing\DTOs;

use App\Domains\Billing\Enums\UsageMetric;

class LimitCheckResult
{
    public function __construct(
        public readonly UsageMetric $metric,
        public readonly bool $allowed,
        public readonly int $current,
        public readonly ?int $limit,
        public readonly ?int $remaining,
        public readonly ?string $message = null,
    ) {}

    public function isUnlimited(): bool
    {
        return $this->limit === null;
    }
}
