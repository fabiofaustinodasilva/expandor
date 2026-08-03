<?php

namespace App\Domains\Analytics\DTOs;

readonly class AnalyticsFiltersDTO
{
    public function __construct(
        public ?string $date_from = null,
        public ?string $date_to = null,
        public ?int $city_id = null,
        public ?int $sector_id = null,
        public ?int $user_id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date_from: $data['date_from'] ?? null,
            date_to: $data['date_to'] ?? null,
            city_id: isset($data['city_id']) ? (int) $data['city_id'] : null,
            sector_id: isset($data['sector_id']) ? (int) $data['sector_id'] : null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
        );
    }
}
