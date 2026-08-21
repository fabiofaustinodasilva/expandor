<?php

namespace App\Domains\Payments\Support;

use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\PaymentMethodType;

final class BillingUiLabels
{
    public static function subscriptionStatus(?string $status): string
    {
        return match ($status) {
            Subscription::STATUS_ACTIVE => 'Ativa',
            Subscription::STATUS_TRIAL => 'Em trial',
            Subscription::STATUS_PAST_DUE => 'Inadimplente',
            Subscription::STATUS_CANCELLED => 'Cancelada',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : '—',
        };
    }

    public static function paymentMethod(?string $method): string
    {
        return match (strtolower((string) $method)) {
            PaymentMethodType::Pix->value, 'pix' => 'PIX',
            PaymentMethodType::Boleto->value, 'boleto' => 'Boleto',
            'card', 'credit_card' => 'Cartão',
            'renewal' => 'Renovação',
            '', null => '—',
            default => ucfirst((string) $method),
        };
    }

    public static function financialStatus(string $status): string
    {
        return match ($status) {
            'em_dia' => 'Em dia',
            'pendente' => 'Pendente',
            'tolerancia' => 'Em tolerância',
            'vencido' => 'Vencido',
            'suspenso' => 'Suspenso',
            default => $status,
        };
    }
}
