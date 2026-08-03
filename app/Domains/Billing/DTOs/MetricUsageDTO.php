<?php

namespace App\Domains\Billing\DTOs;

use App\Domains\Billing\Enums\UsageMetric;

class MetricUsageDTO
{
    public function __construct(
        public readonly UsageMetric $metric,
        public readonly int $current,
        public readonly ?int $limit,
        public readonly int $recorded,
        public readonly string $period,
    ) {}

    public function isUnlimited(): bool
    {
        return $this->limit === null;
    }

    public function remaining(): ?int
    {
        if ($this->limit === null) {
            return null;
        }

        return max(0, $this->limit - $this->current);
    }

    public function usagePercent(): ?float
    {
        if ($this->limit === null || $this->limit === 0) {
            return null;
        }

        return round(min(100, ($this->current / $this->limit) * 100), 1);
    }
}
