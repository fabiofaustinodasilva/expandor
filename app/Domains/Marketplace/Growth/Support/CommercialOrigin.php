<?php

namespace App\Domains\Marketplace\Growth\Support;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;

final class CommercialOrigin
{
    /**
     * @return array{origin: string, campaign: string|null}
     */
    public static function present(?MarketplaceLead $lead): array
    {
        $source = strtolower(trim((string) ($lead?->utm_source ?? '')));
        $medium = strtolower(trim((string) ($lead?->utm_medium ?? '')));
        $campaign = trim((string) ($lead?->utm_campaign ?? '')) ?: null;

        $paid = in_array($medium, ['cpc', 'ppc', 'paid', 'paid_social', 'ads', 'cpm', 'cpa'], true);

        $origin = match (true) {
            $source === '' && $medium === '' => 'Direto',
            in_array($source, ['google', 'googleads', 'adwords'], true) && $paid => 'Google Ads',
            in_array($source, ['google', 'googleads'], true) => 'Orgânico',
            in_array($source, ['facebook', 'fb', 'meta', 'an'], true) => 'Meta Ads',
            in_array($source, ['instagram', 'ig'], true) => 'Instagram',
            in_array($source, ['indicacao', 'indicação', 'referral', 'indica'], true) => 'Indicação',
            in_array($source, ['organic', 'organico', 'orgânico'], true) => 'Orgânico',
            $source !== '' => mb_convert_case(str_replace(['-', '_'], ' ', $source), MB_CASE_TITLE, 'UTF-8'),
            default => 'Direto',
        };

        return [
            'origin' => $origin,
            'campaign' => $campaign,
        ];
    }
}
