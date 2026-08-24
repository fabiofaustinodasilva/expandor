<?php

namespace App\Domains\Payments\Exceptions;

use RuntimeException;

class PaymentGatewayClientException extends RuntimeException
{
    public function __construct(
        protected string $userMessage,
        string $technicalMessage = '',
        protected ?array $gatewayErrors = null,
    ) {
        parent::__construct($technicalMessage !== '' ? $technicalMessage : $userMessage);
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function gatewayErrors(): ?array
    {
        return $this->gatewayErrors;
    }
}
