<?php

namespace App\Domains\SaasGrowth\DTOs;

readonly class CompanyUsageSnapshot
{
    /**
     * @param  array<string, array{value: int, limit: int|null, percent: float|null}>  $metrics
     */
    public function __construct(
        public int $companyId,
        public array $metrics,
        public ?string $planName,
    ) {}

    public function value(string $metric): int
    {
        return (int) ($this->metrics[$metric]['value'] ?? 0);
    }

    public function limit(string $metric): ?int
    {
        $limit = $this->metrics[$metric]['limit'] ?? null;

        return $limit !== null ? (int) $limit : null;
    }

    public function percent(string $metric): ?float
    {
        $percent = $this->metrics[$metric]['percent'] ?? null;

        return $percent !== null ? (float) $percent : null;
    }
}
