<?php

namespace App\Domains\Marketplace\Repositories;

use App\Domains\Marketplace\Enums\MarketplaceSectionType;
use App\Domains\Marketplace\Models\MarketplaceFaq;
use App\Domains\Marketplace\Models\MarketplaceMedia;
use App\Domains\Marketplace\Models\MarketplaceSection;
use App\Domains\Marketplace\Models\MarketplaceTestimonial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MarketplaceContentRepository
{
    public const CACHE_KEY = 'marketplace.public.content.v1';

    /**
     * @return Collection<int, MarketplaceSection>
     */
    public function activeSections(): Collection
    {
        return MarketplaceSection::query()
            ->where('active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, MarketplaceSection>
     */
    public function allSections(): Collection
    {
        return MarketplaceSection::query()
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, MarketplaceTestimonial>
     */
    public function activeTestimonials(): Collection
    {
        return MarketplaceTestimonial::query()
            ->where('active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, MarketplaceFaq>
     */
    public function activeFaqs(): Collection
    {
        return MarketplaceFaq::query()
            ->where('active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, MarketplaceMedia>
     */
    public function activeMedia(?string $type = null): Collection
    {
        $query = MarketplaceMedia::query()
            ->where('active', true)
            ->orderBy('order')
            ->orderBy('id');

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    public function forgetPublicCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function nextSectionOrder(): int
    {
        return (int) MarketplaceSection::query()->max('order') + 1;
    }

    public function findSection(int $id): ?MarketplaceSection
    {
        return MarketplaceSection::query()->find($id);
    }

    public function sectionByType(MarketplaceSectionType $type): ?MarketplaceSection
    {
        return MarketplaceSection::query()
            ->where('type', $type->value)
            ->orderBy('order')
            ->first();
    }
}
