<?php

namespace App\Domains\Communication\Enums;

enum WhatsAppConnectionStatus: string
{
    case DISCONNECTED = 'disconnected';
    case CONNECTING = 'connecting';
    case CONNECTED = 'connected';
    case ERROR = 'error';

    public function label(): string
    {
        return match ($this) {
            self::DISCONNECTED => 'Desconectado',
            self::CONNECTING => 'Conectando',
            self::CONNECTED => 'Conectado',
            self::ERROR => 'Erro',
        };
    }
}
