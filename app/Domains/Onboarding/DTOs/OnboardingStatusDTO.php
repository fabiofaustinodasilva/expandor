<?php

namespace App\Domains\Onboarding\DTOs;

readonly class OnboardingStatusDTO
{
    /**
     * @param  array<int, StepDTO>  $steps
     * @param  array<int, string>  $alerts
     * @param  array<int, array{key:string,label:string}>  $tourStops
     */
    public function __construct(
        public int $companyId,
        public string $status,
        public int $percent,
        public bool $isCompleted,
        public bool $demoGenerated,
        public string $tourStatus,
        public ?string $currentWizardKey,
        public array $steps,
        public array $alerts,
        public array $tourStops,
        public ChecklistDTO $checklist,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'status' => $this->status,
            'percent' => $this->percent,
            'is_completed' => $this->isCompleted,
            'demo_generated' => $this->demoGenerated,
            'tour_status' => $this->tourStatus,
            'current_wizard_key' => $this->currentWizardKey,
            'steps' => array_map(fn (StepDTO $step) => $step->toArray(), $this->steps),
            'alerts' => $this->alerts,
            'tour_stops' => $this->tourStops,
            'checklist' => $this->checklist->toArray(),
        ];
    }
}
