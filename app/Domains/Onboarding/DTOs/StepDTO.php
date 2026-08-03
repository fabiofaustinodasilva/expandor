<?php

namespace App\Domains\Onboarding\DTOs;

readonly class StepDTO
{
    /**
     * @param  array<int, array{id:int,title:string,type:string}>  $trainingSuggestions
     */
    public function __construct(
        public int $id,
        public string $key,
        public string $title,
        public ?string $description,
        public int $sortOrder,
        public ?string $wizardKey,
        public string $status,
        public int $percent,
        public ?string $completedAt,
        public ?int $completedBy,
        public array $trainingSuggestions = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'sort_order' => $this->sortOrder,
            'wizard_key' => $this->wizardKey,
            'status' => $this->status,
            'percent' => $this->percent,
            'completed_at' => $this->completedAt,
            'completed_by' => $this->completedBy,
            'training_suggestions' => $this->trainingSuggestions,
        ];
    }
}
