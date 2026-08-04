<?php

namespace App\Domains\Marketplace\Services;

use App\Domains\Company\Models\Plan;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Repositories\MarketplaceContentRepository;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use App\Domains\Platform\Support\PlanCatalog;
use Illuminate\Support\Collection;

class MarketplacePublicPageService
{
    public function __construct(
        protected MarketplaceSettingsRepository $settings,
        protected MarketplaceContentRepository $content,
    ) {}

    /**
     * @return array{
     *   settings: MarketplaceSetting,
     *   sections: Collection,
     *   testimonials: Collection,
     *   faqs: Collection,
     *   gallery: Collection,
     *   videos: Collection,
     *   plans: Collection,
     *   featureLabels: array<string, string>
     * }
     */
    public function assemble(bool $bypassCache = false): array
    {
        // Conteúdo público é lido direto do banco; cache de settings fica no repositório.
        unset($bypassCache);

        return $this->build();
    }

    /**
     * @return array{
     *   settings: MarketplaceSetting,
     *   sections: Collection,
     *   testimonials: Collection,
     *   faqs: Collection,
     *   gallery: Collection,
     *   videos: Collection,
     *   plans: Collection,
     *   featureLabels: array<string, string>
     * }
     */
    protected function build(): array
    {
        $plans = Plan::query()
            ->where('status', Plan::STATUS_ACTIVE)
            ->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->orderBy('price')
            ->get();

        return [
            'settings' => $this->settings->current(),
            'sections' => $this->content->activeSections(),
            'testimonials' => $this->content->activeTestimonials(),
            'faqs' => $this->content->activeFaqs(),
            'gallery' => $this->content->activeMedia('image'),
            'videos' => $this->content->activeMedia('video'),
            'plans' => $plans,
            'featureLabels' => PlanCatalog::featureLabels(),
        ];
    }
}
