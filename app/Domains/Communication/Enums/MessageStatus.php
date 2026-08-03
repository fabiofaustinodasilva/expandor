<?php

namespace App\Domains\Communication\Enums;

enum MessageStatus: string
{
    case PENDING = 'pending';
    case QUEUED = 'queued';
    case SENT = 'sent';
    case FAILED = 'failed';
    case RECEIVED = 'received';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::QUEUED => 'Na fila',
            self::SENT => 'Enviada',
            self::FAILED => 'Falhou',
            self::RECEIVED => 'Recebida',
        };
    }
}
