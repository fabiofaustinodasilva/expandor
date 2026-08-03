<?php

namespace App\Domains\Security\Enums;

enum PrivacyRequestStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case READY = 'ready';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Processando',
            self::READY => 'Pronto',
            self::COMPLETED => 'Concluído',
            self::FAILED => 'Falhou',
            self::REJECTED => 'Rejeitado',
            self::EXPIRED => 'Expirado',
        };
    }
}
