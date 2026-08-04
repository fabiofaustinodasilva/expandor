<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Growth\Models\MarketplaceCase;
use App\Domains\Marketplace\Growth\Services\MarketplaceCaseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceCasesController extends Controller
{
    public function index(MarketplaceCaseService $cases): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.growth.cases', [
            'cases' => $cases->all(),
        ]);
    }

    public function store(Request $request, MarketplaceCaseService $cases): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:180'],
            'segment' => ['nullable', 'string', 'max:120'],
            'challenge' => ['nullable', 'string', 'max:5000'],
            'solution' => ['nullable', 'string', 'max:5000'],
            'result' => ['nullable', 'string', 'max:5000'],
            'video' => ['nullable', 'string', 'max:500'],
            'active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'file', 'max:8192'],
        ]);

        $cases->create(
            array_merge($validated, ['active' => $request->boolean('active', true)]),
            $request->file('image'),
        );

        return back()->with('success', 'Case adicionado.');
    }

    public function destroy(MarketplaceCase $case, MarketplaceCaseService $cases): RedirectResponse
    {
        $this->authorize('marketplace.manage');
        $cases->delete($case);

        return back()->with('success', 'Case removido.');
    }
}
