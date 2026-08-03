<?php

namespace App\Http\Controllers\Web\CRM;

use App\Domains\CRM\Actions\UpsertSalesGoalAction;
use App\Domains\CRM\Enums\GoalPeriod;
use App\Domains\CRM\Models\SalesGoal;
use App\Domains\CRM\Repositories\CrmMetricsRepository;
use App\Domains\CRM\Repositories\OpportunityRepository;
use App\Domains\CRM\Requests\StoreSalesGoalRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SalesGoalController extends Controller
{
    public function __construct(
        protected CrmMetricsRepository $repository,
        protected OpportunityRepository $opportunities,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', SalesGoal::class);

        return view('crm.goals.index', [
            'goals' => $this->repository->paginateGoals(),
            'sellers' => $this->opportunities->sellerOptions(),
            'periods' => GoalPeriod::options(),
        ]);
    }

    public function store(StoreSalesGoalRequest $request, UpsertSalesGoalAction $action): RedirectResponse
    {
        $this->authorize('create', SalesGoal::class);

        $action->execute($request->validated(), $request->user());

        return back()->with('success', 'Meta salva.');
    }
}
