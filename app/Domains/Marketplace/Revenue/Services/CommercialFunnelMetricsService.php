<?php

namespace App\Domains\Marketplace\Revenue\Services;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use Illuminate\Support\Carbon;

class CommercialFunnelMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $rows = MarketplaceSalesPipeline::query()->get(['stage', 'last_contact_at', 'lead_id']);
        $leads = MarketplaceLead::query()->get(['id', 'created_at']);

        $count = fn (array $stages): int => $rows->filter(
            fn ($row) => in_array(
                $row->stage instanceof PipelineStage ? $row->stage->value : (string) $row->stage,
                $stages,
                true
            )
        )->count();

        $new = $count(['new']);
        $contacted = $count(['contacted']);
        $demoScheduled = $count(['demo_scheduled']);
        $demoDone = $count(['demo_completed']);
        $proposal = $count(['proposal_sent']);
        $closed = $count(['customer']);

        $leadCount = max(1, $leads->count());
        $reachedDemo = $count(['demo_scheduled', 'demo_completed', 'proposal_sent', 'negotiation', 'customer', 'trial_started']);
        $demoBase = max(1, $count(['demo_scheduled', 'demo_completed', 'proposal_sent', 'negotiation', 'customer', 'trial_started']));

        $contactDurations = [];
        foreach ($rows as $row) {
            if ($row->last_contact_at === null) {
                continue;
            }
            $lead = $leads->firstWhere('id', $row->lead_id);
            if ($lead?->created_at === null) {
                continue;
            }
            $contactDurations[] = Carbon::parse($row->last_contact_at)->diffInMinutes($lead->created_at);
        }

        $avgMinutes = $contactDurations === [] ? null : (int) round(array_sum($contactDurations) / count($contactDurations));

        return [
            'new' => $new,
            'awaiting_contact' => $new,
            'contacted' => $contacted,
            'demo_scheduled' => $demoScheduled,
            'demo_completed' => $demoDone,
            'proposal' => $proposal,
            'closed' => $closed,
            'lead_to_demo' => round(($reachedDemo / $leadCount) * 100, 1),
            'demo_to_customer' => round(($closed / $demoBase) * 100, 1),
            'avg_first_contact_minutes' => $avgMinutes,
        ];
    }
}
