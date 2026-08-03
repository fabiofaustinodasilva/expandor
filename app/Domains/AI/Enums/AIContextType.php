<?php

namespace App\Domains\AI\Enums;

enum AIContextType: string
{
    case COMPANY = 'company';
    case SALES = 'sales';
    case TRAINING = 'training';

    public function label(): string
    {
        return match ($this) {
            self::COMPANY => 'Empresa',
            self::SALES => 'Vendas',
            self::TRAINING => 'Treinamento',
        };
    }
}
