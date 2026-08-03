<?php

namespace App\Domains\Company\Support\FieldOps;

/**
 * Política efetiva de operação de campo.
 * Hoje resolvida no nível empresa; $scope reserva campanha para evolução.
 */
readonly class FieldOpsPolicy
{
    public function __construct(
        public PointsVisibility $visibility,
        public PointsDisplay $display,
        public PointEditOthersPolicy $editOthers,
        public PointDeletePolicy $delete,
        public string $scope = 'company',
        public ?int $campaignId = null,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'scope' => $this->scope,
            'campaign_id' => $this->campaignId,
            'points_visibility' => $this->visibility->value,
            'points_display' => $this->display->value,
            'points_edit_others' => $this->editOthers->value,
            'points_delete' => $this->delete->value,
            'allows_ui_filters' => $this->display->allowsUiFilters(),
        ];
    }
}
