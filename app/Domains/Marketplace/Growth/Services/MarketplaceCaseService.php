<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Marketplace\Growth\Models\MarketplaceCase;
use App\Domains\Marketplace\Growth\Repositories\MarketplaceGrowthContentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class MarketplaceCaseService
{
    public function __construct(
        protected MarketplaceGrowthContentRepository $content,
    ) {}

    /** @return Collection<int, MarketplaceCase> */
    public function active(): Collection
    {
        return $this->content->activeCases();
    }

    /** @return Collection<int, MarketplaceCase> */
    public function all(): Collection
    {
        return MarketplaceCase::query()->orderBy('order')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): MarketplaceCase
    {
        $case = new MarketplaceCase([
            'company_name' => $data['company_name'],
            'segment' => $data['segment'] ?? null,
            'challenge' => $data['challenge'] ?? null,
            'solution' => $data['solution'] ?? null,
            'result' => $data['result'] ?? null,
            'video' => $data['video'] ?? null,
            'order' => (int) ($data['order'] ?? ((int) MarketplaceCase::query()->max('order') + 1)),
            'active' => (bool) ($data['active'] ?? true),
        ]);
        if ($image instanceof UploadedFile) {
            $case->image = $image->store('platform/marketplace/cases', 'public');
        }
        $case->save();

        return $case;
    }

    public function delete(MarketplaceCase $case): void
    {
        $case->delete();
    }
}
