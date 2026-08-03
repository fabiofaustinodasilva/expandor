<?php

namespace App\Domains\Onboarding\DTOs;

readonly class EnvironmentProgressDTO
{
    /**
     * @param  list<EnvironmentProgressItem>  $items
     */
    public function __construct(
        public int $percent,
        public array $items,
        public bool $companyCreated,
        public bool $brandingDone,
        public bool $productDone,
        public bool $clientDone,
        public bool $teamDone,
        public bool $saleDone,
    ) {}
}
