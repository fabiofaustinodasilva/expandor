<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Repositories\MarketplaceLeadRepository;
use App\Domains\Marketplace\Growth\Services\LeadActivityService;
use App\Domains\Marketplace\Growth\Services\LeadCaptureService;
use App\Domains\Marketplace\Growth\Services\LeadOutreachService;
use App\Domains\Marketplace\Growth\Support\CommercialOrigin;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceLeadsController extends Controller
{
    public function index(
        MarketplaceLeadRepository $leads,
        LeadOutreachService $outreach,
        MarketplaceSettingsService $settings,
    ): View {
        $this->authorize('marketplace.manage');

        $settingsRow = $settings->current();
        $page = $leads->paginate(25);
        $page->getCollection()->transform(function (MarketplaceLead $lead) use ($outreach, $settingsRow) {
            $lead->setAttribute('origin', CommercialOrigin::present($lead));
            $lead->setAttribute('outreach_url', $outreach->conversationUrl($lead, $settingsRow, $lead->pipeline));
            $lead->setAttribute('tel_url', $outreach->telUrl($lead));

            return $lead;
        });

        return view('platform.marketplace.growth.leads', [
            'leads' => $page,
            'statuses' => MarketplaceLeadStatus::cases(),
        ]);
    }

    public function show(
        MarketplaceLead $lead,
        LeadOutreachService $outreach,
        MarketplaceSettingsService $settings,
    ): View {
        $this->authorize('marketplace.manage');

        $lead->load(['score', 'pipeline', 'activities.actor']);
        $settingsRow = $settings->current();

        return view('platform.marketplace.growth.lead-show', [
            'lead' => $lead,
            'origin' => CommercialOrigin::present($lead),
            'outreachUrl' => $outreach->conversationUrl($lead, $settingsRow, $lead->pipeline),
            'scheduleUrl' => $lead->pipeline && $lead->pipeline->demo_scheduled_at
                ? $outreach->scheduleUrl($lead, $settingsRow, $lead->pipeline)
                : null,
            'telUrl' => $outreach->telUrl($lead),
            'stages' => PipelineStage::cases(),
        ]);
    }

    public function openWhatsapp(
        MarketplaceLead $lead,
        LeadActivityService $activities,
        LeadOutreachService $outreach,
        MarketplaceSettingsService $settings,
    ): RedirectResponse {
        $this->authorize('marketplace.manage');
        $activities->record($lead, 'whatsapp_started', 'WhatsApp iniciado', null, auth()->user());

        $url = $outreach->conversationUrl($lead, $settings->current(), $lead->pipeline);
        if ($url === null) {
            return back()->withErrors(['whatsapp' => 'Este lead não tem WhatsApp cadastrado.']);
        }

        return redirect()->away($url);
    }

    public function updateStatus(
        Request $request,
        MarketplaceLead $lead,
        LeadCaptureService $service,
    ): RedirectResponse {
        $this->authorize('marketplace.manage');

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', MarketplaceLeadStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->updateStatus(
            $lead,
            MarketplaceLeadStatus::from($validated['status']),
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Lead atualizado.');
    }
}
