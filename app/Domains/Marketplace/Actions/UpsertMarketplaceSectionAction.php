<?php

namespace App\Domains\Marketplace\Actions;

use App\Domains\Marketplace\Models\MarketplaceSection;
use App\Domains\Marketplace\Services\MarketplaceSectionService;
use Illuminate\Http\UploadedFile;

class UpsertMarketplaceSectionAction
{
    public function __construct(
        protected MarketplaceSectionService $sections,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): MarketplaceSection
    {
        return $this->sections->create($data, $image);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MarketplaceSection $section, array $data, ?UploadedFile $image = null, bool $removeImage = false): MarketplaceSection
    {
        return $this->sections->update($section, $data, $image, $removeImage);
    }
}
