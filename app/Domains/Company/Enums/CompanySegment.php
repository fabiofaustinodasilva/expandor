<?php

namespace App\Domains\Company\Enums;

enum CompanySegment: string
{
    case INTERNET = 'internet';
    case SOLAR = 'solar';
    case SECURITY = 'security';
    case DOOR_TO_DOOR = 'door_to_door';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::INTERNET => 'Internet',
            self::SOLAR => 'Energia Solar',
            self::SECURITY => 'Segurança',
            self::DOOR_TO_DOOR => 'Venda Porta a Porta',
            self::OTHER => 'Outro',
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
