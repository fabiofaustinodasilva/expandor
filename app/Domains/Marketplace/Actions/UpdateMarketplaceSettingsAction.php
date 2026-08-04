<?php

namespace App\Domains\Marketplace\Actions;

use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;
use Illuminate\Http\UploadedFile;

class UpdateMarketplaceSettingsAction
{
    public function __construct(
        protected MarketplaceSettingsService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $files
     * @param  array<string, bool>  $removals
     */
    public function execute(array $data, array $files = [], array $removals = []): MarketplaceSetting
    {
        return $this->settings->update($data, $files, $removals);
    }
}
