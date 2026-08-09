<?php

namespace App\Domains\Integrations\Enums;

enum CompanyIntegrationStatus: string
{
    case Disconnected = 'disconnected';
    case Connected = 'connected';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Disconnected => 'Não conectado',
            self::Connected => 'Conectado',
            self::Error => 'Erro',
        };
    }
}
