<?php

namespace App\Domains\Marketplace\Revenue\Enums;

enum PipelineStage: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case DemoScheduled = 'demo_scheduled';
    case TrialStarted = 'trial_started';
    case Customer = 'customer';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Novo',
            self::Contacted => 'Contactado',
            self::DemoScheduled => 'Demo agendada',
            self::TrialStarted => 'Trial iniciado',
            self::Customer => 'Cliente',
            self::Lost => 'Perdido',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
