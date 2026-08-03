<?php

namespace App\Domains\CRM\Enums;

enum OpportunityStatus: string
{
    case OPEN = 'open';
    case WON = 'won';
    case LOST = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Aberta',
            self::WON => 'Ganha',
            self::LOST => 'Perdida',
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
