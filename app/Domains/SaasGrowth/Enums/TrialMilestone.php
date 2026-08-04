<?php

namespace App\Domains\SaasGrowth\Enums;

enum TrialMilestone: string
{
    case CompanyCreated = 'company_created';
    case TeamCreated = 'team_created';
    case CustomerCreated = 'customer_created';
    case DealCreated = 'deal_created';
    case FirstSale = 'first_sale';
    case Activated = 'activated';

    public function label(): string
    {
        return match ($this) {
            self::CompanyCreated => 'Empresa criada',
            self::TeamCreated => 'Equipe criada',
            self::CustomerCreated => 'Cliente criado',
            self::DealCreated => 'Negócio criado',
            self::FirstSale => 'Primeira venda',
            self::Activated => 'Ativado',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
