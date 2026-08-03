<?php

namespace App\Domains\Communication\Enums;

enum MessageTemplateEvent: string
{
    case FOLLOW_UP = 'follow_up';
    case INTERESTED = 'interested';
    case INSTALLATION = 'installation';
    case GENERIC = 'generic';

    public function label(): string
    {
        return match ($this) {
            self::FOLLOW_UP => 'Retorno agendado',
            self::INTERESTED => 'Cliente interessado',
            self::INSTALLATION => 'Instalação solicitada',
            self::GENERIC => 'Genérico',
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
