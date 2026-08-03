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
    case WHITE = '#f8fafc';
    case PURPLE = '#a855f7';

    public static function forStatus(PropertyStatus|string $status): self
    {
        return MapCommercialGroup::fromStatus($status)->color();
    }
}
