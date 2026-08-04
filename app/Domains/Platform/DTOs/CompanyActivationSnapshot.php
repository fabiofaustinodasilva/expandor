<?php

namespace App\Domains\Platform\DTOs;

use App\Domains\Platform\Enums\ActivationHealthStatus;

readonly class CompanyActivationSnapshot
{
    /**
     * @param  list<array{key: string, label: string, done: bool, at: ?string}>  $timeline
     * @param  list<array{code: string, severity: string, message: string}>  $alerts
     * @param  array<string, int|string|null>  $usage
     * @param  list<array{key: string, label: string, url: ?string}>  $nextSteps
     */
    public function __construct(
        public int $companyId,
        public string $companyName,
        public int $score,
        public ActivationHealthStatus $status,
        public int $activationPercent,
        public string $onboardingStatus,
        public int $onboardingStep,
        public ?string $lastStepLabel,
        public ?string $lastLoginAt,
        public ?string $lastActivityAt,
        public array $timeline,
        public array $alerts,
        public array $usage,
        public array $nextSteps,
        public bool $isStuck,
    ) {}
}
