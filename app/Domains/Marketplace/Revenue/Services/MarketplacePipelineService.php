<?php

namespace App\Domains\Marketplace\Revenue\Services;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Events\MarketplaceDemoScheduled;
use App\Domains\Marketplace\Revenue\Events\MarketplacePipelineChanged;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MarketplacePipelineService
{
    public function ensureForLead(MarketplaceLead $lead, PipelineStage $stage = PipelineStage::New): MarketplaceSalesPipeline
    {
        return MarketplaceSalesPipeline::query()->firstOrCreate(
            ['lead_id' => $lead->id],
            [
                'stage' => $stage,
                'last_contact_at' => null,
            ],
        );
    }

    public function changeStage(
        MarketplaceSalesPipeline $pipeline,
        PipelineStage $stage,
        ?string $notes = null,
        ?int $assignedUserId = null,
    ): MarketplaceSalesPipeline {
        return DB::transaction(function () use ($pipeline, $stage, $notes, $assignedUserId) {
            $from = $pipeline->stage instanceof PipelineStage
                ? $pipeline->stage
                : PipelineStage::from((string) $pipeline->stage);

            $pipeline->stage = $stage;
            if ($notes !== null) {
                $pipeline->notes = $notes;
            }
            if ($assignedUserId !== null) {
                $pipeline->assigned_user_id = $assignedUserId;
            }
            if (in_array($stage, [PipelineStage::Contacted, PipelineStage::DemoScheduled], true)) {
                $pipeline->last_contact_at = now();
            }
            $pipeline->save();

            event(new MarketplacePipelineChanged($pipeline, $from, $stage));

            if ($stage === PipelineStage::DemoScheduled && $from !== PipelineStage::DemoScheduled) {
                event(new MarketplaceDemoScheduled($pipeline));
            }

            Cache::forget('marketplace.revenue.intelligence.v1');

            return $pipeline->fresh() ?? $pipeline;
        });
    }

    /** @return Collection<int, MarketplaceSalesPipeline> */
    public function all(): Collection
    {
        return MarketplaceSalesPipeline::query()
            ->with(['lead', 'assignee'])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function countByStage(PipelineStage $stage): int
    {
        return MarketplaceSalesPipeline::query()->where('stage', $stage->value)->count();
    }
}
