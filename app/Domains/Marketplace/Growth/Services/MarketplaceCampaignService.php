<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Marketplace\Growth\Models\MarketplaceCampaign;
use App\Domains\Marketplace\Growth\Repositories\MarketplaceGrowthContentRepository;
use Illuminate\Support\Collection;

class MarketplaceCampaignService
{
    public function __construct(
        protected MarketplaceGrowthContentRepository $content,
    ) {}

    /** @return Collection<int, MarketplaceCampaign> */
    public function all(): Collection
    {
        return $this->content->allCampaigns();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MarketplaceCampaign
    {
        return MarketplaceCampaign::query()->create([
            'name' => $data['name'],
            'source' => $data['source'] ?? null,
            'medium' => $data['medium'] ?? null,
            'campaign' => $data['campaign'] ?? null,
            'investment' => (float) ($data['investment'] ?? 0),
            'active' => (bool) ($data['active'] ?? true),
        ]);
    }

    public function delete(MarketplaceCampaign $campaign): void
    {
        $campaign->delete();
    }
}
