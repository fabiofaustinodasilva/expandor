<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Growth\Models\MarketplaceSegmentPage;
use App\Domains\Marketplace\Growth\Services\MarketplaceSegmentPageService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceSegmentsController extends Controller
{
    public function index(MarketplaceSegmentPageService $segments): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.growth.segments', [
            'segments' => $segments->all(),
        ]);
    }

    public function store(Request $request, MarketplaceSegmentPageService $segments): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $validated = $request->validate([
            'slug' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'hero_video' => ['nullable', 'string', 'max:500'],
            'features' => ['nullable', 'string', 'max:10000'],
            'cta_text' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'active' => ['sometimes', 'boolean'],
            'hero_image' => ['nullable', 'file', 'max:8192'],
        ]);

        $segments->create(
            array_merge($validated, ['active' => $request->boolean('active', true)]),
            $request->file('hero_image'),
        );

        return back()->with('success', 'Segmento criado.');
    }

    public function destroy(MarketplaceSegmentPage $segment, MarketplaceSegmentPageService $segments): RedirectResponse
    {
        $this->authorize('marketplace.manage');
        $segments->delete($segment);

        return back()->with('success', 'Segmento removido.');
    }
}
