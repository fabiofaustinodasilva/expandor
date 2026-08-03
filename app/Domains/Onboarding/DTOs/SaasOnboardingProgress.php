<?php

namespace App\Domains\Onboarding\DTOs;

readonly class SaasOnboardingProgress
{
    /**
     * @param  list<array{key: string, label: string, done: bool}>  $checklist
     */
    public function __construct(
        public string $status,
        public int $step,
        public int $percent,
        public array $checklist,
        public bool $completed,
        public ?string $continueUrl,
    ) {}
}
