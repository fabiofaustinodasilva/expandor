<?php

namespace Database\Seeders;

use App\Domains\Marketplace\Enums\MarketplaceSectionType;
use App\Domains\Marketplace\Models\MarketplaceFaq;
use App\Domains\Marketplace\Models\MarketplaceSection;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Models\MarketplaceTestimonial;
use App\Domains\Marketplace\Repositories\MarketplaceContentRepository;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class MarketplaceDefaultSeeder extends Seeder
{
    /**
     * Popula conteúdo padrão do Marketplace.
     * Sem force: só quando não existe configuração.
     * Com force: restaura Hero, Features, FAQ, Testimonials, SEO e CTA.
     */
    public function run(bool $force = false): void
    {
        $defaults = config('marketplace_defaults', []);
        $settingsDefaults = $defaults['settings'] ?? [];

        if ($force || ! MarketplaceSetting::query()->exists()) {
            $setting = MarketplaceSetting::query()->latest('id')->first();

            if ($setting === null) {
                MarketplaceSetting::query()->create($settingsDefaults);
            } elseif ($force) {
                $setting->fill([
                    'title' => $settingsDefaults['title'] ?? $setting->title,
                    'subtitle' => $settingsDefaults['subtitle'] ?? $setting->subtitle,
                    'description' => $settingsDefaults['description'] ?? $setting->description,
                    'primary_color' => $settingsDefaults['primary_color'] ?? $setting->primary_color,
                    'secondary_color' => $settingsDefaults['secondary_color'] ?? $setting->secondary_color,
                    'background_color' => $settingsDefaults['background_color'] ?? $setting->background_color,
                    'button_color' => $settingsDefaults['button_color'] ?? $setting->button_color,
                    'seo_title' => $settingsDefaults['seo_title'] ?? $setting->seo_title,
                    'seo_description' => $settingsDefaults['seo_description'] ?? $setting->seo_description,
                    'seo_keywords' => $settingsDefaults['seo_keywords'] ?? $setting->seo_keywords,
                ]);
                $setting->save();
            }
        }

        if ($force) {
            MarketplaceSection::query()->delete();
            MarketplaceTestimonial::query()->delete();
            MarketplaceFaq::query()->delete();
        }

        if ($force || ! MarketplaceSection::query()->exists()) {
            foreach (($defaults['section_order'] ?? []) as $type) {
                $row = $defaults['sections'][$type] ?? null;
                if ($row === null) {
                    continue;
                }

                $description = $row['description'] ?? null;
                if ($type === MarketplaceSectionType::Features->value) {
                    $description = json_encode($row['features'] ?? [], JSON_UNESCAPED_UNICODE);
                }

                MarketplaceSection::query()->create([
                    'type' => $type,
                    'title' => $row['title'] ?? null,
                    'subtitle' => $row['subtitle'] ?? null,
                    'description' => $description,
                    'image' => $row['image'] ?? null,
                    'video' => $row['video'] ?? null,
                    'button_text' => $row['button_text'] ?? null,
                    'button_url' => $row['button_url'] ?? null,
                    'order' => $row['order'] ?? 0,
                    'active' => true,
                ]);
            }
        }

        if ($force || ! MarketplaceTestimonial::query()->exists()) {
            foreach (($defaults['testimonials'] ?? []) as $row) {
                MarketplaceTestimonial::query()->create($row);
            }
        }

        if ($force || ! MarketplaceFaq::query()->exists()) {
            foreach (($defaults['faqs'] ?? []) as $row) {
                MarketplaceFaq::query()->create($row);
            }
        }

        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);
        Cache::forget(MarketplaceContentRepository::CACHE_KEY);
    }
}
