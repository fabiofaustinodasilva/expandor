<?php

namespace App\Domains\Payments\Enums;

enum PaymentMethodType: string
{
    case Card = 'card';
    case Pix = 'pix';
    case Boleto = 'boleto';
}
