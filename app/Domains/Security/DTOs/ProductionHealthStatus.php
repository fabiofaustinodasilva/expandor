<?php

namespace App\Domains\Security\DTOs;

class ProductionHealthStatus
{
    /**
     * @param  array<string, mixed>  $checks
     */
    public function __construct(
        public readonly bool $ok,
        public readonly array $checks,
        public readonly int $failedJobs,
    ) {}
}
