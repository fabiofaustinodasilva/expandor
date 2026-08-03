<?php

namespace App\Domains\Onboarding\DTOs;

readonly class EnvironmentProgressItem
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $done,
        public ?string $route = null,
    ) {}
}
