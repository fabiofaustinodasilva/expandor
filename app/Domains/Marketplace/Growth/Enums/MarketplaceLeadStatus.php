<?php

namespace App\Domains\Marketplace\Growth\Enums;

enum MarketplaceLeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case TrialStarted = 'trial_started';
    case Converted = 'converted';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Novo',
            self::Contacted => 'Contactado',
            self::TrialStarted => 'Teste iniciado',
            self::Converted => 'Convertido',
            self::Lost => 'Perdido',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
