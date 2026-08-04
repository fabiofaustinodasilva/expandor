<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Repositories\MarketplaceLeadRepository;
use App\Domains\Marketplace\Growth\Services\LeadCaptureService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceLeadsController extends Controller
{
    public function index(MarketplaceLeadRepository $leads): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.growth.leads', [
            'leads' => $leads->paginate(25),
            'statuses' => MarketplaceLeadStatus::cases(),
        ]);
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
