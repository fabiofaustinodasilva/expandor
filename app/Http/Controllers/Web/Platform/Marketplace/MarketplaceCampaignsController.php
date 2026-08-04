<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Growth\Models\MarketplaceCampaign;
use App\Domains\Marketplace\Growth\Services\MarketplaceCampaignService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceCampaignsController extends Controller
{
    public function index(MarketplaceCampaignService $campaigns): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.growth.campaigns', [
            'campaigns' => $campaigns->all(),
        ]);
    }

    public function store(Request $request, MarketplaceCampaignService $campaigns): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'source' => ['nullable', 'string', 'max:120'],
            'medium' => ['nullable', 'string', 'max:120'],
            'campaign' => ['nullable', 'string', 'max:180'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $campaigns->create(array_merge($validated, [
            'active' => $request->boolean('active', true),
        ]));

        return back()->with('success', 'Campanha criada.');
    }

    public function destroy(MarketplaceCampaign $campaign, MarketplaceCampaignService $campaigns): RedirectResponse
    {
        $this->authorize('marketplace.manage');
        $campaigns->delete($campaign);

        return back()->with('success', 'Campanha removida.');
    }
}
