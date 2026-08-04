<?php

namespace App\Domains\SaasGrowth\Enums;

enum UsageMetric: string
{
    case Users = 'users';
    case Customers = 'customers';
    case Deals = 'deals';
    case StorageMb = 'storage_mb';

    public function label(): string
    {
        return match ($this) {
            self::Users => 'Usuários',
            self::Customers => 'Clientes',
            self::Deals => 'Negócios',
            self::StorageMb => 'Armazenamento (MB)',
        };
    }
}
