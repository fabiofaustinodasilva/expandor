<?php

namespace App\Domains\Maps\DTOs;

readonly class MapFiltersDTO
{
    /**
     * @param  list<string>|null  $commercial_groups
     * @param  list<int>|null  $creator_ids
     * @param  list<string>|null  $forced_statuses  Status impostos pela política da empresa
     */
    public function __construct(
        public ?int $city_id = null,
        public ?int $sector_id = null,
        public ?string $property_status = null,
        public ?string $date_from = null,
        public ?string $date_to = null,
        public ?float $min_latitude = null,
        public ?float $max_latitude = null,
        public ?float $min_longitude = null,
        public ?float $max_longitude = null,
        public ?array $commercial_groups = null,
        public ?int $user_id = null,
        public ?int $campaign_id = null,
        public ?array $creator_ids = null,
        public ?array $forced_statuses = null,
        public bool $allow_ui_filters = true,
        public ?string $q = null,
        public ?int $search_owner_user_id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $groups = $data['commercial_groups'] ?? null;
        if (is_string($groups) && $groups !== '') {
            $groups = array_values(array_filter(array_map('trim', explode(',', $groups))));
        }
        if (is_array($groups)) {
            $groups = array_values(array_filter(array_map('strval', $groups)));
        } else {
            $groups = null;
        }

        $creatorIds = $data['creator_ids'] ?? null;
        if (is_array($creatorIds)) {
            $creatorIds = array_values(array_unique(array_map('intval', $creatorIds)));
        } else {
            $creatorIds = null;
        }

        $forced = $data['forced_statuses'] ?? null;
        if (is_array($forced)) {
            $forced = array_values(array_filter(array_map('strval', $forced)));
        } else {
            $forced = null;
        }

        $q = isset($data['q']) ? trim((string) $data['q']) : null;
        if ($q === '') {
            $q = null;
        }

        return new self(
            city_id: isset($data['city_id']) ? (int) $data['city_id'] : null,
            sector_id: isset($data['sector_id']) ? (int) $data['sector_id'] : null,
            property_status: $data['property_status'] ?? null,
            date_from: $data['date_from'] ?? null,
            date_to: $data['date_to'] ?? null,
            min_latitude: isset($data['min_latitude']) ? (float) $data['min_latitude'] : null,
            max_latitude: isset($data['max_latitude']) ? (float) $data['max_latitude'] : null,
            min_longitude: isset($data['min_longitude']) ? (float) $data['min_longitude'] : null,
            max_longitude: isset($data['max_longitude']) ? (float) $data['max_longitude'] : null,
            commercial_groups: $groups,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            campaign_id: isset($data['campaign_id']) ? (int) $data['campaign_id'] : null,
            creator_ids: $creatorIds,
            forced_statuses: $forced,
            allow_ui_filters: array_key_exists('allow_ui_filters', $data)
                ? (bool) $data['allow_ui_filters']
                : true,
            q: $q,
            search_owner_user_id: isset($data['search_owner_user_id'])
                ? (int) $data['search_owner_user_id']
                : null,
        );
    }

    public function hasBoundingBox(): bool
    {
        return $this->min_latitude !== null
            && $this->max_latitude !== null
            && $this->min_longitude !== null
            && $this->max_longitude !== null;
    }

    public function hasTextSearch(): bool
    {
        return $this->q !== null && $this->q !== '';
    }

    /**
     * @param  list<int>|null  $creatorIds
     * @param  list<string>|null  $forcedStatuses
     */
    public function withFieldOpsConstraints(
        ?array $creatorIds,
        ?array $forcedStatuses,
        bool $allowUiFilters,
    ): self {
        $groups = $allowUiFilters ? $this->commercial_groups : null;
        $propertyStatus = $allowUiFilters ? $this->property_status : null;

        $creators = $creatorIds;
        if ($this->user_id !== null) {
            if ($creators === null) {
                $creators = [$this->user_id];
            } else {
                $creators = array_values(array_intersect($creators, [$this->user_id]));
                if ($creators === []) {
                    $creators = [-1];
                }
            }
        }

        return new self(
            city_id: $this->city_id,
            sector_id: $this->sector_id,
            property_status: $propertyStatus,
            date_from: $this->date_from,
            date_to: $this->date_to,
            min_latitude: $this->min_latitude,
            max_latitude: $this->max_latitude,
            min_longitude: $this->min_longitude,
            max_longitude: $this->max_longitude,
            commercial_groups: $groups,
            user_id: null,
            campaign_id: $this->campaign_id,
            creator_ids: $creators,
            forced_statuses: $forcedStatuses,
            allow_ui_filters: $allowUiFilters,
            q: $this->q,
            search_owner_user_id: $this->search_owner_user_id,
        );
    }

    /**
     * Busca textual: sem bbox (empresa/escopo) e, para seller, só próprios (criados ou visitados).
     */
    public function forTextSearch(?int $sellerOwnerUserId): self
    {
        return new self(
            city_id: $this->city_id,
            sector_id: $this->sector_id,
            property_status: $this->property_status,
            date_from: $this->date_from,
            date_to: $this->date_to,
            min_latitude: null,
            max_latitude: null,
            min_longitude: null,
            max_longitude: null,
            commercial_groups: $this->commercial_groups,
            user_id: null,
            campaign_id: $this->campaign_id,
            creator_ids: null,
            forced_statuses: $this->forced_statuses,
            allow_ui_filters: $this->allow_ui_filters,
            q: $this->q,
            search_owner_user_id: $sellerOwnerUserId,
        );
    }
}
