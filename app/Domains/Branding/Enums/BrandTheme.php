<?php

namespace App\Domains\Branding\Enums;

enum BrandTheme: string
{
    case Dark = 'dark';
    case Light = 'light';

    public function label(): string
    {
        return match ($this) {
            self::Dark => 'Escuro',
            self::Light => 'Claro',
        };
    }
}
