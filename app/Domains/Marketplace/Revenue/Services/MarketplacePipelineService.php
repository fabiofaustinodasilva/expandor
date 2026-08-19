<?php

namespace App\Domains\Marketplace\Revenue\Services;

use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Services\LeadActivityService;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Events\MarketplaceDemoScheduled;
use App\Domains\Marketplace\Revenue\Events\MarketplacePipelineChanged;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MarketplacePipelineService
{
    public function __construct(
        protected LeadActivityService $activities,
    ) {}

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
            $pipeline->loadMissing('lead');
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

            $this->syncLeadStatus($pipeline, $stage);

            if ($from !== $stage && $pipeline->lead) {
                $this->activities->record(
                    $pipeline->lead,
                    'stage_changed',
                    $stage->label(),
                    $from->label().' → '.$stage->label(),
                    auth()->user(),
                );
            }

            event(new MarketplacePipelineChanged($pipeline, $from, $stage));

            if ($stage === PipelineStage::DemoScheduled && $from !== PipelineStage::DemoScheduled) {
                event(new MarketplaceDemoScheduled($pipeline));
            }

            Cache::forget('marketplace.revenue.intelligence.v1');

            return $pipeline->fresh(['lead.score', 'lead']) ?? $pipeline;
        });
    }

    public function scheduleDemo(
        MarketplaceSalesPipeline $pipeline,
        \DateTimeInterface $when,
        ?string $observation = null,
    ): MarketplaceSalesPipeline {
        $pipeline->forceFill([
            'demo_scheduled_at' => $when,
            'next_action_at' => $when,
            'next_action_label' => 'Demonstração',
        ])->save();

        return $this->changeStage(
            $pipeline->fresh(['lead']) ?? $pipeline,
            PipelineStage::DemoScheduled,
            $observation,
        );
    }

    /** @return Collection<int, MarketplaceSalesPipeline> */
    public function all(bool $includeLost = false): Collection
    {
        return MarketplaceSalesPipeline::query()
            ->with(['lead.score', 'assignee'])
            ->when(! $includeLost, fn ($query) => $query->where('stage', '!=', PipelineStage::Lost->value))
            ->orderByDesc('updated_at')
            ->get();
    }

    public function countByStage(PipelineStage $stage): int
    {
        return MarketplaceSalesPipeline::query()->where('stage', $stage->value)->count();
    }

    protected function syncLeadStatus(MarketplaceSalesPipeline $pipeline, PipelineStage $stage): void
    {
        $lead = $pipeline->lead;
        if ($lead === null) {
            return;
        }

        $status = match ($stage) {
            PipelineStage::Contacted => MarketplaceLeadStatus::Contacted,
            PipelineStage::Customer => MarketplaceLeadStatus::Converted,
            PipelineStage::Lost => MarketplaceLeadStatus::Lost,
            PipelineStage::TrialStarted => MarketplaceLeadStatus::TrialStarted,
            default => null,
        };

        if ($status === null) {
            return;
        }

        $lead->status = $status;
        $lead->save();
    }
}
