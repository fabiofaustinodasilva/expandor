<?php

namespace App\Domains\Commissions\Enums;

enum ProductCommissionType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Valor fixo',
            self::Percentage => 'Porcentagem',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
