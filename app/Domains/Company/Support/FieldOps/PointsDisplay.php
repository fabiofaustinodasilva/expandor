<?php

namespace App\Domains\Company\Support\FieldOps;

use App\Domains\Maps\Enums\MapCommercialGroup;
use App\Domains\Sales\Properties\Enums\PropertyStatus;

/**
 * Exibição padrão dos pontos no mapa (nível empresa; futuro: campanha).
 */
enum PointsDisplay: string
{
    case All = 'all';
    case ActiveOnly = 'active_only';
    case PendingOnly = 'pending_only';
    case InterestedOnly = 'interested_only';
    case CustomersOnly = 'customers_only';
    case AllWithFilters = 'all_with_filters';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Todos',
            self::ActiveOnly => 'Apenas ativos',
            self::PendingOnly => 'Apenas pendentes',
            self::InterestedOnly => 'Apenas interessados',
            self::CustomersOnly => 'Apenas clientes',
            self::AllWithFilters => 'Todos com filtros',
        };
    }

    /**
     * Status forçados pela política. null = sem filtro de status (ou livre para UI).
     *
     * @return list<string>|null
     */
    public function forcedStatusValues(): ?array
    {
        return match ($this) {
            self::All, self::AllWithFilters => null,
            self::ActiveOnly => array_values(array_diff(
                array_column(PropertyStatus::cases(), 'value'),
                [PropertyStatus::NO_INTEREST->value]
            )),
            self::PendingOnly => [
                PropertyStatus::NEW->value,
                PropertyStatus::RETURN_LATER->value,
            ],
            self::InterestedOnly => MapCommercialGroup::INTERESTED->statusValues(),
            self::CustomersOnly => MapCommercialGroup::CUSTOMER->statusValues(),
        };
    }

    public function allowsUiFilters(): bool
    {
        return $this === self::AllWithFilters;
    }

    /**
     * @return list<self>
     */
    public static function casesOrdered(): array
    {
        return [
            self::All,
            self::ActiveOnly,
            self::PendingOnly,
            self::InterestedOnly,
            self::CustomersOnly,
            self::AllWithFilters,
        ];
    }
}
