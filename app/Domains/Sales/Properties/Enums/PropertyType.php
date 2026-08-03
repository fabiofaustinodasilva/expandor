<?php

namespace App\Domains\Sales\Properties\Enums;

enum PropertyType: string
{
    case HOUSE = 'house';
    case APARTMENT = 'apartment';
    case COMMERCIAL = 'commercial';
    case LAND = 'land';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HOUSE => 'Casa',
            self::APARTMENT => 'Apartamento',
            self::COMMERCIAL => 'Comercial',
            self::LAND => 'Terreno',
            self::OTHER => 'Outro',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
