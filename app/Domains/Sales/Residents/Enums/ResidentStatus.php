<?php

namespace App\Domains\Sales\Residents\Enums;

enum ResidentStatus: string
{
    case ACTIVE = 'active';
    case MOVED = 'moved';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::MOVED => 'Mudou-se',
            self::INACTIVE => 'Inativo',
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
