<?php

namespace App\Domains\CRM\Enums;

enum GoalPeriod: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Mensal',
            self::QUARTERLY => 'Trimestral',
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
