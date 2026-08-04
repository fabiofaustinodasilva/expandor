<?php

namespace App\Domains\Marketplace\Growth\Repositories;

use App\Domains\Marketplace\Growth\Models\MarketplaceCase;
use App\Domains\Marketplace\Growth\Models\MarketplaceCampaign;
use App\Domains\Marketplace\Growth\Models\MarketplaceSegmentPage;
use Illuminate\Support\Collection;

class MarketplaceGrowthContentRepository
{
    /** @return Collection<int, MarketplaceSegmentPage> */
    public function allSegments(): Collection
    {
        return MarketplaceSegmentPage::query()->orderBy('title')->get();
    }

    public function findSegmentBySlug(string $slug): ?MarketplaceSegmentPage
    {
        return MarketplaceSegmentPage::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->first();
    }

    /** @return Collection<int, MarketplaceCase> */
    public function activeCases(): Collection
    {
        return MarketplaceCase::query()->where('active', true)->orderBy('order')->get();
    }

    /** @return Collection<int, MarketplaceCampaign> */
    public function allCampaigns(): Collection
    {
        return MarketplaceCampaign::query()->orderByDesc('id')->get();
    }
}
