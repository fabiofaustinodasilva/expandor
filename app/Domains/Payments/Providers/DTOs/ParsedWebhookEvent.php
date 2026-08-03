<?php

namespace App\Domains\Payments\Providers\DTOs;

readonly class ParsedWebhookEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $eventId,
        public string $eventType,
        public array $payload,
        public ?string $gatewayPaymentId = null,
        public ?string $gatewayCheckoutId = null,
        public ?string $gatewaySubscriptionId = null,
        public ?string $gatewayCustomerId = null,
        public ?float $amount = null,
        public ?string $paymentMethod = null,
        public bool $isPaymentConfirmed = false,
        public bool $isPaymentFailed = false,
        public bool $isSubscriptionCancelled = false,
    ) {}
}
