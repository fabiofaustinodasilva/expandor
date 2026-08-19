<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Services\LeadActivityService;
use App\Domains\Marketplace\Growth\Services\LeadOutreachService;
use App\Domains\Marketplace\Growth\Support\CommercialOrigin;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use App\Domains\Marketplace\Revenue\Services\CommercialFunnelMetricsService;
use App\Domains\Marketplace\Revenue\Services\MarketplacePipelineService;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplacePipelineController extends Controller
{
    public function index(
        Request $request,
        MarketplacePipelineService $pipeline,
        CommercialFunnelMetricsService $metrics,
        LeadOutreachService $outreach,
        MarketplaceSettingsService $settings,
    ): View {
        $this->authorize('marketplace.manage');

        $includeLost = $request->boolean('lost');
        $settingsRow = $settings->current();

        $items = $pipeline->all($includeLost)->map(function (MarketplaceSalesPipeline $item) use ($outreach, $settingsRow) {
            $item->outreach_url = $item->lead ? $outreach->conversationUrl($item->lead, $settingsRow, $item) : null;
            $item->schedule_url = $item->lead && $item->demo_scheduled_at
                ? $outreach->scheduleUrl($item->lead, $settingsRow, $item)
                : null;
            $item->tel_url = $item->lead ? $outreach->telUrl($item->lead) : null;
            $item->origin = CommercialOrigin::present($item->lead);

            return $item;
        });

        $columns = [];
        foreach (PipelineStage::kanbanColumns() as $key => $label) {
            $columns[$key] = [
                'label' => $label,
                'items' => $items->filter(
                    fn (MarketplaceSalesPipeline $item) => ($item->stage?->kanbanColumn() ?? 'new') === $key
                )->values(),
            ];
        }

        return view('platform.marketplace.revenue.pipeline', [
            'items' => $items,
            'columns' => $columns,
            'stages' => PipelineStage::cases(),
            'metrics' => $metrics->snapshot(),
            'includeLost' => $includeLost,
            'lostItems' => $includeLost
                ? $items->filter(fn (MarketplaceSalesPipeline $item) => $item->stage === PipelineStage::Lost)
                : collect(),
        ]);
    }

    public function update(
        Request $request,
        MarketplaceSalesPipeline $pipeline,
        MarketplacePipelineService $service,
    ): RedirectResponse {
        $this->authorize('marketplace.manage');

        $validated = $request->validate([
            'stage' => ['required', 'in:'.implode(',', PipelineStage::values())],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $service->changeStage(
            $pipeline,
            PipelineStage::from($validated['stage']),
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Estágio atualizado.');
    }

    public function schedule(
        Request $request,
        MarketplaceSalesPipeline $pipeline,
        MarketplacePipelineService $service,
    ): RedirectResponse {
        $this->authorize('marketplace.manage');

        $validated = $request->validate([
            'demo_date' => ['required', 'date'],
            'demo_time' => ['required', 'date_format:H:i'],
            'observation' => ['nullable', 'string', 'max:2000'],
        ]);

        $when = \Carbon\Carbon::parse($validated['demo_date'].' '.$validated['demo_time'], config('app.timezone'));
        $service->scheduleDemo($pipeline, $when, $validated['observation'] ?? null);

        return back()->with('success', 'Demonstração agendada.');
    }

    public function openWhatsapp(
        MarketplaceSalesPipeline $pipeline,
        LeadActivityService $activities,
        LeadOutreachService $outreach,
        MarketplaceSettingsService $settings,
    ): RedirectResponse {
        $this->authorize('marketplace.manage');
        $pipeline->loadMissing('lead');

        if ($pipeline->lead) {
            $activities->record(
                $pipeline->lead,
                'whatsapp_started',
                'WhatsApp iniciado',
                null,
                auth()->user(),
            );

            if ($pipeline->last_contact_at === null) {
                $pipeline->forceFill(['last_contact_at' => now()])->save();
            }
        }

        $url = $pipeline->lead
            ? $outreach->conversationUrl($pipeline->lead, $settings->current(), $pipeline)
            : null;

        if ($url === null) {
            return back()->withErrors(['whatsapp' => 'Este lead não tem WhatsApp cadastrado.']);
        }

        return redirect()->away($url);
    }
}
