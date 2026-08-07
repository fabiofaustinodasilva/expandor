<?php

namespace App\Domains\Maps\Enums;

use App\Domains\Sales\Properties\Enums\PropertyStatus;

enum MapMarkerColor: string
{
    case GREEN = '#22c55e';
    case BLUE = '#3b82f6';
    case ORANGE = '#f97316';
    case YELLOW = '#eab308';
    case RED = '#ef4444';
    case GRAY = '#9ca3af';
    case SLATE = '#64748b';
    case WHITE = '#f8fafc';
    case PURPLE = '#a855f7';

    /**
     * Marker color by PropertyStatus (presentation).
     * Sprint 8.2.10: return_later and no_interest are visually distinct
     * even though both still map to MapCommercialGroup::VISITED for filters.
     */
    public static function forStatus(PropertyStatus|string $status): self
    {
        $status = $status instanceof PropertyStatus
            ? $status
            : PropertyStatus::from($status);

        return match ($status) {
            PropertyStatus::CUSTOMER,
            PropertyStatus::INSTALLATION_REQUESTED => self::GREEN,
            PropertyStatus::INTERESTED => self::BLUE,
            PropertyStatus::RETURN_LATER => self::ORANGE,
            PropertyStatus::NO_INTEREST => self::SLATE,
            PropertyStatus::NEW => self::RED,
        };
    }

    /**
     * Compact mark drawn inside the pin (not emoji) — aids color-blind users.
     */
    public static function markForStatus(PropertyStatus|string $status): string
    {
        $status = $status instanceof PropertyStatus
            ? $status
            : PropertyStatus::from($status);

        return match ($status) {
            PropertyStatus::RETURN_LATER => 'R',
            PropertyStatus::NO_INTEREST => '×',
            PropertyStatus::INSTALLATION_REQUESTED,
            PropertyStatus::CUSTOMER => '',
            PropertyStatus::INTERESTED => '',
            PropertyStatus::NEW => '',
        };
    }
}
