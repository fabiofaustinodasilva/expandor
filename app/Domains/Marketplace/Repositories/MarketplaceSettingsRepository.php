<?php

namespace App\Domains\Marketplace\Repositories;

use App\Domains\Marketplace\Models\MarketplaceSetting;
use Illuminate\Support\Facades\Cache;

class MarketplaceSettingsRepository
{
    public const CACHE_KEY = 'marketplace.settings.singleton';

    public function current(): MarketplaceSetting
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            $row = MarketplaceSetting::query()->latest('id')->first();

            if ($row !== null) {
                return $row;
            }

            $defaults = config('marketplace_defaults.settings', []);

            return MarketplaceSetting::query()->create([
                'title' => $defaults['title'] ?? 'Expandor — CRM inteligente para vendas',
                'subtitle' => $defaults['subtitle'] ?? 'CRM + Pipeline + WhatsApp + Equipe + Gestão Comercial em uma única plataforma.',
                'description' => $defaults['description'] ?? 'Expandor — CRM de campo e gestão comercial.',
                'primary_color' => $defaults['primary_color'] ?? '#3B82F6',
                'secondary_color' => $defaults['secondary_color'] ?? '#0F172A',
                'background_color' => $defaults['background_color'] ?? '#0B1220',
                'button_color' => $defaults['button_color'] ?? '#F59E0B',
                'seo_title' => $defaults['seo_title'] ?? 'Expandor — CRM inteligente para vendas',
                'seo_description' => $defaults['seo_description'] ?? 'Teste grátis o Expandor.',
                'seo_keywords' => $defaults['seo_keywords'] ?? 'crm, vendas, saas, expandor',
            ]);
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function save(MarketplaceSetting $setting): MarketplaceSetting
    {
        $setting->save();
        $this->forgetCache();

        $fresh = $setting->fresh() ?? $setting;
        // Regrava o cache imediatamente para a Landing refletir o save sem esperar o próximo miss.
        Cache::put(self::CACHE_KEY, $fresh, now()->addMinutes(10));

        return $fresh;
    }
}
