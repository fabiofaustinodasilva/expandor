<?php

namespace App\Domains\Platform\DTOs;

class CompanyOperationalMetrics
{
    public function __construct(
        public readonly string $createdAt,
        public readonly ?string $lastAccessAt,
        public readonly ?string $planName,
        public readonly ?string $subscriptionStatus,
        public readonly ?int $trialDaysRemaining,
        public readonly ?string $trialEndsAt,
        public readonly ?string $endsAt,
        public readonly ?string $nextBillingAt,
        public readonly int $usersCount,
        public readonly int $customersCount,
        public readonly int $visitsCount,
        public readonly float $storageUsedMb,
        public readonly ?int $storageLimitMb,
    ) {}
}
