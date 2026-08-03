<?php

namespace App\Domains\CRM\Enums;

enum LeadSource: string
{
    case MANUAL = 'manual';
    case VISIT = 'visit';
    case CAMPAIGN = 'campaign';
    case WHATSAPP = 'whatsapp';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::VISIT => 'Visita',
            self::CAMPAIGN => 'Campanha',
            self::WHATSAPP => 'WhatsApp',
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
