<?php

namespace App\Domains\Maps\Enums;

use App\Domains\Sales\Properties\Enums\PropertyStatus;

/**
 * Commercial presentation groups for the operational map.
 * Derived only from existing PropertyStatus — no new business rules.
 */
enum MapCommercialGroup: string
{
    case CUSTOMER = 'customer';
    case INTERESTED = 'interested';
    case VISITED = 'visited';
    case NEW = 'new';

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Cliente ativo',
            self::INTERESTED => 'Interessado',
            self::VISITED => 'Visitado',
            self::NEW => 'Novo / sem abordagem',
        };
    }

    public function color(): MapMarkerColor
    {
        return match ($this) {
            self::CUSTOMER => MapMarkerColor::GREEN,
            self::INTERESTED => MapMarkerColor::BLUE,
            // Aggregate filter group — pins use MapMarkerColor::forStatus() instead.
            self::VISITED => MapMarkerColor::YELLOW,
            self::NEW => MapMarkerColor::RED,
        };
    }

    /**
     * @return list<PropertyStatus>
     */
    public function statuses(): array
    {
        return match ($this) {
            self::CUSTOMER => [
                PropertyStatus::CUSTOMER,
                PropertyStatus::INSTALLATION_REQUESTED,
            ],
            self::INTERESTED => [
                PropertyStatus::INTERESTED,
            ],
            self::VISITED => [
                PropertyStatus::RETURN_LATER,
                PropertyStatus::NO_INTEREST,
            ],
            self::NEW => [
                PropertyStatus::NEW,
            ],
        };
    }

    /**
     * @return list<string>
     */
    public function statusValues(): array
    {
        return array_map(
            static fn (PropertyStatus $status) => $status->value,
            $this->statuses()
        );
    }

    public static function fromStatus(PropertyStatus|string $status): self
    {
        $status = $status instanceof PropertyStatus
            ? $status
            : PropertyStatus::from($status);

        return match ($status) {
            PropertyStatus::CUSTOMER,
            PropertyStatus::INSTALLATION_REQUESTED => self::CUSTOMER,
            PropertyStatus::INTERESTED => self::INTERESTED,
            PropertyStatus::RETURN_LATER,
            PropertyStatus::NO_INTEREST => self::VISITED,
            PropertyStatus::NEW => self::NEW,
        };
    }

    /**
     * Visual legend for the map (pins + labels).
     * Retorno and Sem interesse are listed separately even though both
     * remain under commercial_group=visited for API filters.
     *
     * @return list<array{group: string, label: string, color: string, mark: string, status?: string}>
     */
    public static function legend(): array
    {
        return [
            [
                'group' => self::CUSTOMER->value,
                'label' => 'Cliente / instalação',
                'color' => MapMarkerColor::GREEN->value,
                'mark' => '',
            ],
            [
                'group' => self::INTERESTED->value,
                'label' => 'Interessado',
                'color' => MapMarkerColor::BLUE->value,
                'mark' => '',
            ],
            [
                'group' => 'return',
                'status' => PropertyStatus::RETURN_LATER->value,
                'label' => 'Retorno',
                'color' => MapMarkerColor::ORANGE->value,
                'mark' => 'R',
            ],
            [
                'group' => 'no_interest',
                'status' => PropertyStatus::NO_INTEREST->value,
                'label' => 'Sem interesse',
                'color' => MapMarkerColor::SLATE->value,
                'mark' => '×',
            ],
            [
                'group' => self::NEW->value,
                'label' => 'Novo',
                'color' => MapMarkerColor::RED->value,
                'mark' => '',
            ],
        ];
    }
}
