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

            return MarketplaceSetting::query()->create([
                'title' => 'Transforme sua empresa em uma operação inteligente',
                'subtitle' => 'Controle clientes, vendas, equipes e processos em um único sistema.',
                'description' => 'Expandor — CRM de campo e gestão comercial para equipes que vendem com inteligência.',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#0F172A',
                'background_color' => '#0B1220',
                'button_color' => '#F59E0B',
                'seo_title' => 'Expandor — CRM inteligente para vendas',
                'seo_description' => 'Controle clientes, vendas, equipes e processos em um único sistema. Teste grátis.',
                'seo_keywords' => 'crm, vendas, saas, expandor, gestão comercial',
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

        return $setting->fresh() ?? $setting;
    }
}
