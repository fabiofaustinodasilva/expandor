<?php

namespace App\Domains\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayTransaction extends Model
{
    protected $fillable = [
        'checkout_session_id',
        'payment_record_id',
        'gateway',
        'payment_method',
        'payment_id',
        'status',
        'pix_qr_code',
        'pix_qr_code_base64',
        'pix_expiration_at',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'pix_expiration_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    public function checkoutSession(): BelongsTo
    {
        return $this->belongsTo(CheckoutSession::class);
    }

    public function paymentRecord(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_record_id');
    }

    public function isPix(): bool
    {
        return strtolower((string) $this->payment_method) === 'pix';
    }
}
