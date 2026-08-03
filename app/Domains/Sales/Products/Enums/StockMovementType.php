<?php

namespace App\Domains\Sales\Products\Enums;

enum StockMovementType: string
{
    case ENTRY = 'entry';
    case SALE = 'sale';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::ENTRY => 'Entrada',
            self::SALE => 'Venda',
            self::ADJUSTMENT => 'Ajuste',
        };
    }
}
