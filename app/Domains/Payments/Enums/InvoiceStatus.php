<?php

namespace App\Domains\Payments\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';
    case Uncollectible = 'uncollectible';

    public function isPayable(): bool
    {
        return in_array($this, [self::Open, self::Overdue], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Open => 'Pendente',
            self::Paid => 'Paga',
            self::Overdue => 'Vencida',
            self::Void => 'Cancelada',
            self::Uncollectible => 'Incobrável',
        };
    }
}
