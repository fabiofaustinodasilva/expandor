<?php

namespace App\Domains\Platform\DTOs;

readonly class FeatureFlagStateDTO
{
    public function __construct(
        public int $flagId,
        public string $key,
        public string $name,
        public ?string $description,
        public bool $defaultEnabled,
        public bool $enabled,
        public bool $hasOverride,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'flag_id' => $this->flagId,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'default_enabled' => $this->defaultEnabled,
            'enabled' => $this->enabled,
            'has_override' => $this->hasOverride,
        ];
    }
}
