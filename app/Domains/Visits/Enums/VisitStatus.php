<?php

namespace App\Domains\Visits\Enums;

use App\Domains\Sales\Properties\Enums\PropertyStatus;

enum VisitStatus: string
{
    case INTERESTED = 'interested';
    case INSTALLATION_REQUESTED = 'installation_requested';
    case RETURN_LATER = 'return_later';
    case NO_INTEREST = 'no_interest';
    case NOT_HOME = 'not_home';
    case WRONG_ADDRESS = 'wrong_address';

    public function label(): string
    {
        return match ($this) {
            self::INTERESTED => 'Interessado',
            self::INSTALLATION_REQUESTED => 'Instalação solicitada',
            self::RETURN_LATER => 'Retornar depois',
            self::NO_INTEREST => 'Sem interesse',
            self::NOT_HOME => 'Não encontrado',
            self::WRONG_ADDRESS => 'Endereço incorreto',
        };
    }

    /**
     * Linguagem comercial de campo (UI). Não altera o label formal.
     */
    public function commercialLabel(): string
    {
        return match ($this) {
            self::INSTALLATION_REQUESTED => '🟢 Contratou',
            self::INTERESTED => '🔵 Interessado',
            self::RETURN_LATER => '🟡 Retorno marcado',
            self::NO_INTEREST => '🔴 Sem interesse',
            self::NOT_HOME => '⚪ Não encontrado',
            self::WRONG_ADDRESS => '⚪ Endereço incorreto',
        };
    }

    public function toPropertyStatus(): ?PropertyStatus
    {
        return match ($this) {
            self::INTERESTED => PropertyStatus::INTERESTED,
            self::INSTALLATION_REQUESTED => PropertyStatus::INSTALLATION_REQUESTED,
            self::RETURN_LATER => PropertyStatus::RETURN_LATER,
            self::NO_INTEREST => PropertyStatus::NO_INTEREST,
            self::NOT_HOME,
            self::WRONG_ADDRESS => null,
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
