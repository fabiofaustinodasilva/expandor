<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use App\Domains\Marketplace\Revenue\Services\MarketplacePipelineService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplacePipelineController extends Controller
{
    public function index(MarketplacePipelineService $pipeline): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.revenue.pipeline', [
            'items' => $pipeline->all(),
            'stages' => PipelineStage::cases(),
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

        return back()->with('success', 'Pipeline atualizado.');
    }
}
