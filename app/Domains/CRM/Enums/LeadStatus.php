<?php

namespace App\Domains\CRM\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case DISQUALIFIED = 'disqualified';
    case CONVERTED = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Novo',
            self::CONTACTED => 'Contatado',
            self::QUALIFIED => 'Qualificado',
            self::DISQUALIFIED => 'Desqualificado',
            self::CONVERTED => 'Convertido',
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
