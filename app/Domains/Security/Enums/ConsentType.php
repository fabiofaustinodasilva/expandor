<?php

namespace App\Domains\Security\Enums;

enum ConsentType: string
{
    case DATA_PROCESSING = 'data_processing';
    case MARKETING = 'marketing';
    case WHATSAPP = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::DATA_PROCESSING => 'Tratamento de dados',
            self::MARKETING => 'Marketing',
            self::WHATSAPP => 'WhatsApp',
        };
    }
}
