<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Platform\Requests\StorePlatformPlanRequest;
use App\Domains\Platform\Requests\UpdatePlatformPlanRequest;
use App\Domains\Platform\Services\PlatformPlanService;
use App\Domains\Platform\Support\PlanCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformPlanController extends Controller
{
    public function __construct(
        protected PlatformPlanService $plans,
    ) {}

    public function index(): View
    {
        $this->authorize('platform.managePlans');

        return view('platform.plans.index', [
            'plans' => $this->plans->paginate(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('platform.managePlans');

        return view('platform.plans.create', [
            'featureLabels' => PlanCatalog::featureLabels(),
        ]);
    }

    public function store(StorePlatformPlanRequest $request): RedirectResponse
    {
        $plan = $this->plans->create($request->validated(), $request->user());

        return redirect()
            ->route('platform.plans.index')
            ->with('success', "Plano {$plan->name} criado.");
    }

    public function edit(int $plan): View
    {
        $this->authorize('platform.managePlans');
        $model = $this->plans->find($plan);

        return view('platform.plans.edit', [
            'plan' => $model,
            'featureLabels' => PlanCatalog::featureLabels(),
            'featureMap' => $model->featureMap(),
        ]);
    }

    public function update(UpdatePlatformPlanRequest $request, int $plan): RedirectResponse
    {
        $model = $this->plans->find($plan);
        $updated = $this->plans->update($model, $request->validated(), $request->user());

        return redirect()
            ->route('platform.plans.edit', $updated)
            ->with('success', 'Plano atualizado.');
    }

    public function activate(Request $request, int $plan): RedirectResponse
    {
        $this->authorize('platform.managePlans');
        $model = $this->plans->find($plan);
        $this->plans->activate($model, $request->user());

        return back()->with('success', 'Plano ativado.');
    }

    public function deactivate(Request $request, int $plan): RedirectResponse
    {
        $this->authorize('platform.managePlans');
        $model = $this->plans->find($plan);
        $this->plans->deactivate($model, $request->user());

        return back()->with('success', 'Plano desativado.');
    }
}
