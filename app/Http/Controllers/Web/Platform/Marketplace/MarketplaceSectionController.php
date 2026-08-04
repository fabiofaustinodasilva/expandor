<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Actions\UpsertMarketplaceSectionAction;
use App\Domains\Marketplace\Enums\MarketplaceSectionType;
use App\Domains\Marketplace\Models\MarketplaceSection;
use App\Domains\Marketplace\Requests\UpsertMarketplaceSectionRequest;
use App\Domains\Marketplace\Services\MarketplaceSectionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceSectionController extends Controller
{
    public function index(MarketplaceSectionService $sections): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.sections.index', [
            'sections' => $sections->all(),
            'types' => MarketplaceSectionType::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.sections.form', [
            'section' => null,
            'types' => MarketplaceSectionType::cases(),
        ]);
    }

    public function store(
        UpsertMarketplaceSectionRequest $request,
        UpsertMarketplaceSectionAction $action,
    ): RedirectResponse {
        $action->create($request->validated(), $request->file('image'));

        return redirect()
            ->route('platform.marketplace.sections.index')
            ->with('success', 'Seção criada.');
    }

    public function edit(MarketplaceSection $section): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.sections.form', [
            'section' => $section,
            'types' => MarketplaceSectionType::cases(),
        ]);
    }

    public function update(
        UpsertMarketplaceSectionRequest $request,
        MarketplaceSection $section,
        UpsertMarketplaceSectionAction $action,
    ): RedirectResponse {
        $action->update(
            $section,
            $request->validated(),
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        return redirect()
            ->route('platform.marketplace.sections.index')
            ->with('success', 'Seção atualizada.');
    }

    public function destroy(MarketplaceSection $section, MarketplaceSectionService $sections): RedirectResponse
    {
        $this->authorize('marketplace.manage');
        $sections->delete($section);

        return redirect()
            ->route('platform.marketplace.sections.index')
            ->with('success', 'Seção removida.');
    }

    public function toggle(MarketplaceSection $section, MarketplaceSectionService $sections): RedirectResponse
    {
        $this->authorize('marketplace.manage');
        $sections->toggleActive($section);

        return back()->with('success', $section->active ? 'Seção ativada.' : 'Seção desativada.');
    }

    public function reorder(Request $request, MarketplaceSectionService $sections): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:marketplace_sections,id'],
        ]);

        $sections->reorder($validated['order']);

        return back()->with('success', 'Ordem das seções atualizada.');
    }
}
