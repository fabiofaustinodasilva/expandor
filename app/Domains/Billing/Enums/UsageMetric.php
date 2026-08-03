<?php

namespace App\Domains\Billing\Enums;

enum UsageMetric: string
{
    case USERS = 'users';
    case PROPERTIES = 'properties';
    case CAMPAIGNS = 'campaigns';
    case MESSAGES = 'messages';
    case AI_TOKENS = 'ai_tokens';

    public function label(): string
    {
        return match ($this) {
            self::USERS => 'Usuários',
            self::PROPERTIES => 'Imóveis',
            self::CAMPAIGNS => 'Campanhas',
            self::MESSAGES => 'Mensagens',
            self::AI_TOKENS => 'Tokens de IA',
        };
    }
}
