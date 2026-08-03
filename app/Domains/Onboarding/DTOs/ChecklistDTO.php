<?php

namespace App\Domains\Onboarding\DTOs;

readonly class ChecklistDTO
{
    /**
     * @param  array<int, StepDTO>  $steps
     * @param  array<int, string>  $alerts
     */
    public function __construct(
        public array $steps,
        public int $percent,
        public string $status,
        public array $alerts,
        public bool $demoGenerated,
        public string $tourStatus,
        public ?string $nextStepKey,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'steps' => array_map(fn (StepDTO $step) => $step->toArray(), $this->steps),
            'percent' => $this->percent,
            'status' => $this->status,
            'alerts' => $this->alerts,
            'demo_generated' => $this->demoGenerated,
            'tour_status' => $this->tourStatus,
            'next_step_key' => $this->nextStepKey,
        ];
    }
}
