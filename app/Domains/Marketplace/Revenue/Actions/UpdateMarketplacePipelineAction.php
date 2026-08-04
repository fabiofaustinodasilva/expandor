<?php

namespace App\Domains\Marketplace\Revenue\Actions;

use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use App\Domains\Marketplace\Revenue\Services\MarketplacePipelineService;

class UpdateMarketplacePipelineAction
{
    public function __construct(
        protected MarketplacePipelineService $pipeline,
    ) {}

    public function execute(
        MarketplaceSalesPipeline $pipeline,
        PipelineStage $stage,
        ?string $notes = null,
        ?int $assignedUserId = null,
    ): MarketplaceSalesPipeline {
        return $this->pipeline->changeStage($pipeline, $stage, $notes, $assignedUserId);
    }
}
