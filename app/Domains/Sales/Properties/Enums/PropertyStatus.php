<?php

namespace App\Domains\Sales\Properties\Enums;

enum PropertyStatus: string
{
    case NEW = 'new';
    case INTERESTED = 'interested';
    case INSTALLATION_REQUESTED = 'installation_requested';
    case RETURN_LATER = 'return_later';
    case NO_INTEREST = 'no_interest';
    case CUSTOMER = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Novo',
            self::INTERESTED => 'Interessado',
            self::INSTALLATION_REQUESTED => 'Instalação solicitada',
            self::RETURN_LATER => 'Retornar depois',
            self::NO_INTEREST => 'Sem interesse',
            self::CUSTOMER => 'Cliente',
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
