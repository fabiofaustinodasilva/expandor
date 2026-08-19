<?php

namespace App\Domains\Marketplace\Services;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\User;
use App\Domains\Marketplace\Enums\MarketplaceSectionType;
use App\Domains\Marketplace\Growth\Services\MarketplaceCaseService;
use App\Domains\Marketplace\Models\MarketplaceFaq;
use App\Domains\Marketplace\Models\MarketplaceSection;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Models\MarketplaceTestimonial;
use App\Domains\Marketplace\Repositories\MarketplaceContentRepository;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use App\Domains\Platform\Support\PlanCatalog;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MarketplacePublicPageService
{
    public function __construct(
        protected MarketplaceSettingsRepository $settings,
        protected MarketplaceContentRepository $content,
        protected MarketplaceCaseService $cases,
    ) {}

    /**
     * Monta a landing pública.
     * Fluxo: defaults (config) → CMS sobrescreve → payload nunca vazio.
     *
     * @return array{
     *   settings: MarketplaceSetting,
     *   sections: Collection<int, MarketplaceSection>,
     *   testimonials: Collection<int, MarketplaceTestimonial>,
     *   faqs: Collection<int, MarketplaceFaq>,
     *   gallery: Collection,
     *   videos: Collection,
     *   plans: Collection,
     *   cases: Collection,
     *   featureLabels: array<string, string>,
     *   whatsappContext: string|null,
     *   nav: array<int, array{label: string, href: string}>,
     *   navActions: array<int, array<string, mixed>>,
     *   footer: array<string, mixed>,
     *   brand: string,
     *   videoFallbackImage: string,
     *   heroFallbackImage: string
     * }
     */
    public function assemble(bool $bypassCache = false): array
    {
        unset($bypassCache);

        return $this->build();
    }

    /**
     * @return array<string, mixed>
     */
    protected function build(): array
    {
        $defaults = config('marketplace_defaults', []);
        $settings = $this->overlaySettings($this->settings->current(), $defaults['settings'] ?? []);

        $allSections = $this->content->allSections()
            ->filter(fn (MarketplaceSection $section) => $section->type instanceof MarketplaceSectionType);

        $cmsSections = $allSections
            ->where('active', true)
            ->values()
            ->keyBy(fn (MarketplaceSection $section) => $section->type->value);

        $configuredTypes = $allSections
            ->map(fn (MarketplaceSection $section) => $section->type->value)
            ->unique()
            ->values()
            ->all();

        $sections = collect();
        foreach (($defaults['section_order'] ?? []) as $type) {
            if ($cmsSections->has($type)) {
                $section = $cmsSections->get($type);
                if ($type === MarketplaceSectionType::Features->value) {
                    $section = $this->ensureFeaturesPayload($section, $defaults);
                }
                $sections->push($this->sanitizePublicSection($section, $type, $defaults));
                continue;
            }

            // Tipo já cadastrado no CMS e inativo: respeita a escolha do admin (não reintroduz default).
            // CTA é exceção: a landing comercial nunca fica sem chamada final.
            if (in_array($type, $configuredTypes, true) && $type !== MarketplaceSectionType::Cta->value) {
                continue;
            }

            $sections->push($this->makeDefaultSection($type, $defaults));
        }

        foreach ($cmsSections as $type => $section) {
            if (! in_array($type, $defaults['section_order'] ?? [], true)) {
                $sections->push($section);
            }
        }

        $cmsTestimonials = $this->content->activeTestimonials()
            ->reject(fn (MarketplaceTestimonial $row) => $this->isInventedTestimonial($row))
            ->values();
        $testimonials = $cmsTestimonials->isNotEmpty()
            ? $cmsTestimonials
            : $this->defaultTestimonials($defaults);

        $cmsFaqs = $this->content->activeFaqs();
        $faqs = $cmsFaqs->isNotEmpty() || MarketplaceFaq::query()->exists()
            ? $this->sanitizePublicFaqs($cmsFaqs, $defaults)
            : $this->defaultFaqs($defaults);

        $plans = Plan::query()
            ->where('status', Plan::STATUS_ACTIVE)
            ->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->orderBy('price')
            ->get();

        $gallery = $this->content->activeMedia('image');
        $premium = $this->resolvePremium($defaults, $settings, $gallery);

        return [
            'settings' => $settings,
            'sections' => $sections->values(),
            'testimonials' => $testimonials->values(),
            'faqs' => $faqs->values(),
            'gallery' => $gallery,
            'videos' => $this->content->activeMedia('video'),
            'plans' => $plans,
            'cases' => $this->cases->active(),
            'featureLabels' => PlanCatalog::featureLabels(),
            'whatsappContext' => 'Origem: Site Expandor',
            'nav' => $defaults['nav'] ?? [],
            'navActions' => $defaults['nav_actions'] ?? [],
            'footer' => $premium['footer'] ?? ($defaults['footer'] ?? []),
            'brand' => $defaults['brand'] ?? 'Expandor',
            'videoFallbackImage' => $defaults['sections']['video']['image'] ?? '/images/marketplace/product/hero-mapa.png',
            'heroFallbackImage' => $defaults['sections']['hero']['image'] ?? '/images/marketplace/product/hero-mapa.png',
            'heroSecondary' => [
                'text' => $defaults['sections']['hero']['button_text_secondary'] ?? 'Ver o Expandor em ação',
                'url' => $defaults['sections']['hero']['button_url_secondary'] ?? '#produto',
            ],
            'premium' => $premium,
            'metrics' => $this->platformMetrics($defaults, $settings),
            'ui' => $premium['ui'] ?? ($defaults['ui'] ?? []),
            'demoForm' => $premium['demo_form'] ?? ($defaults['demo_form'] ?? []),
            'tracking' => $settings->conversionOverrides()['tracking'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    protected function resolvePremium(array $defaults, MarketplaceSetting $settings, Collection $gallery): array
    {
        unset($gallery);
        $overrides = $settings->conversionOverrides();

        return [
            'showcase' => $overrides['showcase'] ?? $defaults['showcase'] ?? [],
            'field_ops' => $overrides['field_ops'] ?? $defaults['field_ops'] ?? [],
            'journey' => $overrides['journey'] ?? $defaults['journey'] ?? [],
            'manager' => $overrides['manager'] ?? $defaults['manager'] ?? [],
            'map_memory' => $overrides['map_memory'] ?? $defaults['map_memory'] ?? [],
            'sale_close' => $overrides['sale_close'] ?? $defaults['sale_close'] ?? [],
            'commercial_plans' => $defaults['commercial_plans'] ?? [],
            'demo_cta' => $defaults['demo_cta'] ?? [],
            'how_it_works' => $overrides['how_it_works'] ?? $defaults['how_it_works'] ?? [],
            'before_after' => $overrides['before_after'] ?? $defaults['before_after'] ?? [
                'before' => [],
                'after' => [],
            ],
            'benefits' => $overrides['benefits'] ?? $defaults['benefits'] ?? [],
            'segments' => $overrides['segments'] ?? $defaults['segments'] ?? [],
            'social_proof_title' => $overrides['social_proof_title']
                ?? ($defaults['social_proof']['title'] ?? 'Construído a partir da operação real de vendas em campo.'),
            'client_logos' => $overrides['client_logos'] ?? [],
            'ui' => array_merge($defaults['ui'] ?? [], $overrides['ui'] ?? []),
            'demo_form' => array_merge($defaults['demo_form'] ?? [], $overrides['demo_form'] ?? []),
            'footer' => array_replace_recursive($defaults['footer'] ?? [], $overrides['footer'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return list<array{key: string, label: string, value: int}>
     */
    protected function platformMetrics(array $defaults, MarketplaceSetting $settings): array
    {
        $overrides = $settings->conversionOverrides();
        $labels = collect($defaults['social_proof']['metrics'] ?? [])
            ->keyBy('key');

        $counts = Cache::remember('marketplace.public.metrics.v1', now()->addMinutes(5), function () {
            return [
                'sellers' => (int) User::query()->withoutGlobalScopes()->count(),
                'customers' => (int) Property::query()->withoutGlobalScopes()->count(),
                'visits' => (int) Visit::query()->withoutGlobalScopes()->count(),
                'campaigns' => (int) Campaign::query()->withoutGlobalScopes()->count(),
            ];
        });

        if (isset($overrides['metrics']) && is_array($overrides['metrics'])) {
            foreach ($overrides['metrics'] as $key => $value) {
                if (is_numeric($value)) {
                    $counts[$key] = (int) $value;
                }
            }
        }

        $metrics = [];
        foreach (['sellers', 'customers', 'visits', 'campaigns'] as $key) {
            $metrics[] = [
                'key' => $key,
                'label' => $labels[$key]['label'] ?? ucfirst($key),
                'value' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $metrics;
    }

    /**
     * Aplica defaults apenas em campos textuais/SEO vazios.
     * Nunca sobrescreve flags de WhatsApp/redes (evita apagar configuração do CMS).
     *
     * @param  array<string, mixed>  $defaultSettings
     */
    protected function overlaySettings(MarketplaceSetting $settings, array $defaultSettings): MarketplaceSetting
    {
        // Clona para não mutar a instância em cache.
        $settings = clone $settings;

        $skip = [
            'whatsapp_enabled', 'instagram_enabled', 'facebook_enabled',
            'youtube_enabled', 'linkedin_enabled', 'tiktok_enabled', 'twitter_enabled',
            'whatsapp_number', 'instagram_url', 'facebook_url', 'youtube_url',
            'linkedin_url', 'tiktok_url', 'twitter_url',
        ];

        foreach ($defaultSettings as $key => $value) {
            if (in_array($key, $skip, true)) {
                continue;
            }

            $current = $settings->getAttribute($key);
            if ($current === null || $current === '' || $this->isStaleCommercialCopy((string) $current)) {
                $settings->setAttribute($key, $value);
            }
        }

        return $settings;
    }

    /**
     * Remove jargão técnico visível ao visitante (CMS antigo), preferindo defaults limpos.
     *
     * @param  array<string, mixed>  $defaults
     */
    protected function sanitizePublicSection(MarketplaceSection $section, string $type, array $defaults): MarketplaceSection
    {
        $fallback = $defaults['sections'][$type] ?? [];
        foreach (['title', 'subtitle', 'description', 'button_text', 'button_url'] as $field) {
            $value = (string) ($section->{$field} ?? '');
            if ($value !== '' && ($this->containsTechnicalJargon($value) || $this->isStaleCommercialCopy($value))) {
                $replacement = $fallback[$field] ?? null;
                if ($field === 'description' && $type === MarketplaceSectionType::Features->value) {
                    $replacement = json_encode($fallback['features'] ?? [], JSON_UNESCAPED_UNICODE);
                }
                if (is_string($replacement) && $replacement !== '') {
                    $section->{$field} = $replacement;
                }
            }
        }

        $image = (string) ($section->image ?? '');
        if (
            in_array($type, [MarketplaceSectionType::Hero->value, MarketplaceSectionType::About->value, MarketplaceSectionType::Video->value], true)
            && ($image === '' || str_contains($image, 'dashboard.svg') || str_contains($image, 'hero-saas.svg') || str_contains($image, 'product-preview.svg'))
        ) {
            $section->image = $fallback['image'] ?? $section->image;
        }

        return $section;
    }

    protected function isStaleCommercialCopy(string $text): bool
    {
        $normalized = trim($text);
        if ($normalized === '') {
            return false;
        }

        foreach ($this->staleCommercialPhrases() as $phrase) {
            if ($normalized === $phrase || str_contains($normalized, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function staleCommercialPhrases(): array
    {
        return [
            'Organize sua equipe de vendas porta a porta e venda mais',
            'Organize sua equipe de vendas porta a porta',
            'Controle visitas, clientes e vendedores no mapa',
            'Veja o Expandor em ação',
            'Telas da operação de campo',
            'Escolha o plano ideal',
            'Comece pequeno e cresça com a sua equipe',
            'Começar agora',
            'Pronto para organizar sua equipe de vendas?',
            'Veja como funciona na prática',
            'Uma visão rápida do dia a dia no Expandor',
            'Posso testar sem cartão?',
            'Quem já usa recomenda',
            'Empresas organizam suas equipes de campo com Expandor',
            'Números da operação',
            'Sistema para vendas porta a porta',
            'Indicadores em tempo real',
            'Veja vendedores e território no mapa em tempo real',
            'Organize vendedores, visitas e clientes',
        ];
    }

    protected function containsTechnicalJargon(string $text): bool
    {
        return (bool) preg_match(
            '/\b(marketplace|saas|landing|pipeline|growth|revenue|trial|multi[\s-]?tenant|roi)\b|teste\s+gr[aá]tis/iu',
            $text
        );
    }

    /**
     * @param  array<string, mixed>  $defaults
     */
    protected function makeDefaultSection(string $type, array $defaults): MarketplaceSection
    {
        $row = $defaults['sections'][$type] ?? [];
        $description = $row['description'] ?? null;

        if ($type === MarketplaceSectionType::Features->value) {
            $description = json_encode($row['features'] ?? [], JSON_UNESCAPED_UNICODE);
        }

        return MarketplaceSection::make([
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

    /**
     * @param  array<string, mixed>  $defaults
     */
    protected function ensureFeaturesPayload(MarketplaceSection $section, array $defaults): MarketplaceSection
    {
        $items = json_decode((string) $section->description, true);
        if (is_array($items) && $items !== []) {
            return $section;
        }

        $section->description = json_encode(
            $defaults['sections']['features']['features'] ?? [],
            JSON_UNESCAPED_UNICODE
        );

        return $section;
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return Collection<int, MarketplaceTestimonial>
     */
    protected function defaultTestimonials(array $defaults): Collection
    {
        return collect($defaults['testimonials'] ?? [])
            ->map(fn (array $row) => MarketplaceTestimonial::make($row));
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return Collection<int, MarketplaceFaq>
     */
    protected function defaultFaqs(array $defaults): Collection
    {
        return collect($defaults['faqs'] ?? [])
            ->map(fn (array $row) => MarketplaceFaq::make($row));
    }

    protected function isInventedTestimonial(MarketplaceTestimonial $row): bool
    {
        return in_array($row->name, ['Ana Ribeiro', 'Lucas Mendes', 'Carla Souza'], true);
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return Collection<int, MarketplaceFaq>
     */
    protected function sanitizePublicFaqs(Collection $faqs, array $defaults): Collection
    {
        $kept = $faqs
            ->reject(fn (MarketplaceFaq $faq) => $this->faqAdvertisesFreePlan($faq))
            ->values();

        if ($kept->isEmpty()) {
            return $this->defaultFaqs($defaults);
        }

        $hasErp = $kept->contains(
            fn (MarketplaceFaq $faq) => str_contains(mb_strtolower((string) $faq->question), 'erp')
        );

        if (! $hasErp) {
            $erp = collect($defaults['faqs'] ?? [])->first(
                fn (array $row) => str_contains(mb_strtolower((string) ($row['question'] ?? '')), 'erp')
            );
            if (is_array($erp)) {
                $kept->prepend(MarketplaceFaq::make($erp));
            }
        }

        return $kept->values();
    }

    protected function faqAdvertisesFreePlan(MarketplaceFaq $faq): bool
    {
        $blob = mb_strtolower(trim($faq->question.' '.$faq->answer));

        return (bool) preg_match(
            '/posso testar sem cart[aã]o|come[cç]ar gr[aá]tis|plano gratuito para sempre|experimentar o expandor com a sua equipe antes/iu',
            $blob
        );
    }
}
