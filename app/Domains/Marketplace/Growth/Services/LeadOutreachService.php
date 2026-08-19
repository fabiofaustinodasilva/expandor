<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Marketplace\Growth\Support\BrazilianPhone;
use App\Domains\Marketplace\Growth\Support\CommercialMessageTemplates;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;

class LeadOutreachService
{
    public function conversationUrl(MarketplaceLead $lead, MarketplaceSetting $settings, ?MarketplaceSalesPipeline $pipeline = null): ?string
    {
        $digits = $lead->whatsappDigits();
        if ($digits === null) {
            return null;
        }

        $template = trim((string) ($settings->commercial_outreach_template ?: ''))
            ?: CommercialMessageTemplates::defaultOutreach();

        $text = CommercialMessageTemplates::render($template, $lead, $settings, $pipeline);

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }

    public function scheduleUrl(MarketplaceLead $lead, MarketplaceSetting $settings, MarketplaceSalesPipeline $pipeline): ?string
    {
        $digits = $lead->whatsappDigits();
        if ($digits === null) {
            return null;
        }

        $template = trim((string) ($settings->commercial_schedule_template ?: ''))
            ?: CommercialMessageTemplates::defaultSchedule();

        $text = CommercialMessageTemplates::render($template, $lead, $settings, $pipeline);

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }

    public function telUrl(MarketplaceLead $lead): ?string
    {
        $digits = BrazilianPhone::normalize((string) $lead->phone);
        if ($digits === null) {
            return null;
        }

        return 'tel:+'.$digits;
    }
}
