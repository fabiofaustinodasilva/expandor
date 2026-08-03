<?php

namespace App\Domains\Sales\Residents\Enums;

enum ResidentHistoryEvent: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case STATUS_CHANGED = 'status_changed';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Cadastro',
            self::UPDATED => 'Atualização',
            self::STATUS_CHANGED => 'Alteração de status',
        };
    }
}
