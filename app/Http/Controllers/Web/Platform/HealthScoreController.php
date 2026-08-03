<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Platform\Services\HealthScoreService;
use App\Domains\Platform\Services\PlatformCompanyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HealthScoreController extends Controller
{
    public function __construct(
        protected HealthScoreService $health,
        protected PlatformCompanyService $platform,
    ) {}

    public function index(): View
    {
        $this->authorize('platform.viewHealth');

        return view('platform.health.index', [
            'aggregate' => $this->health->aggregate(),
            'companies' => $this->platform->paginateCompanies(50),
        ]);
    }

    public function recalculate(Request $request, int $company): RedirectResponse
    {
        $this->authorize('platform.viewHealth');
        $model = $this->platform->findClient($company);
        $this->health->calculate($model, $request->user());

        return back()->with('success', 'Health score recalculado.');
    }

    public function recalculateAll(Request $request): RedirectResponse
    {
        $this->authorize('platform.viewHealth');
        $this->health->recalculateAll($request->user());

        return back()->with('success', 'Health scores recalculados para todos os clientes.');
    }
}
