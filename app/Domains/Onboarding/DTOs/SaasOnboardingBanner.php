<?php

namespace App\Domains\Onboarding\DTOs;

readonly class SaasOnboardingBanner
{
    public function __construct(
        public bool $show,
        public int $percent,
        public ?string $continueUrl,
    ) {}
}
