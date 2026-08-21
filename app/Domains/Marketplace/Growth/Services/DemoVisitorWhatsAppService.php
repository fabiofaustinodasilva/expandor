<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Support\CommercialMessageTemplates;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use Illuminate\Support\Facades\Log;

class DemoVisitorWhatsAppService
{
    public function conversationUrl(MarketplaceLead $lead, MarketplaceSetting $settings): ?string
    {
        if (! $settings->hasWhatsAppButton()) {
            Log::info('Demo confirmation WhatsApp CTA skipped: commercial WhatsApp number not configured.', [
                'lead_id' => $lead->id,
            ]);

            return null;
        }

        $digits = $settings->whatsappDigits();
        if ($digits === null) {
            Log::info('Demo confirmation WhatsApp CTA skipped: commercial WhatsApp digits unavailable.', [
                'lead_id' => $lead->id,
            ]);

            return null;
        }

        $text = CommercialMessageTemplates::visitorDemoRequest($lead);

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }
}
