<?php

namespace App\Domains\Communication\Enums;

enum MessageDirection: string
{
    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';

    public function label(): string
    {
        return match ($this) {
            self::INBOUND => 'Recebida',
            self::OUTBOUND => 'Enviada',
        };
    }
}
