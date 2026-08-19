<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Communication\Providers\DTOs\WhatsAppSendResult;
use App\Domains\Communication\Providers\ProviderFactory;
use Throwable;

class CommercialWhatsAppGateway
{
    public function __construct(
        protected ProviderFactory $providers,
    ) {}

    public function isConfigured(): bool
    {
        $token = (string) (config('whatsapp.providers.wppconnect.token') ?: '');
        $base = (string) (config('whatsapp.providers.wppconnect.base_url') ?: '');

        return $token !== '' && $base !== '';
    }

    public function send(string $to, string $body): WhatsAppSendResult
    {
        if (! $this->isConfigured()) {
            return WhatsAppSendResult::fail('WhatsApp comercial não configurado (WppConnect).');
        }

        try {
            return $this->providers->make(null)->sendMessage($to, $body);
        } catch (Throwable $exception) {
            return WhatsAppSendResult::fail($exception->getMessage());
        }
    }
}
