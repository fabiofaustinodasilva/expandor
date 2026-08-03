<?php

namespace App\Domains\Payments\Enums;

enum CheckoutStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Provisioned = 'provisioned';
    case Failed = 'failed';
}
