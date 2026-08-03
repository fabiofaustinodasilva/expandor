<?php

namespace App\Http\Controllers\Web\CRM;

use App\Domains\CRM\Models\CommissionRule;
use App\Domains\CRM\Repositories\CrmMetricsRepository;
use App\Domains\CRM\Requests\StoreCommissionRuleRequest;
use App\Domains\CRM\Services\CommissionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function __construct(
        protected CommissionService $commissions,
        protected CrmMetricsRepository $repository,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', CommissionRule::class);

        return view('crm.commissions.index', [
            'rules' => $this->repository->paginateCommissionRules(),
            'entries' => $this->repository->paginateCommissionEntries(),
        ]);
    }

    public function storeRule(StoreCommissionRuleRequest $request): RedirectResponse
    {
        $this->authorize('create', CommissionRule::class);

        $this->commissions->createRule($request->validated(), $request->user());

        return back()->with('success', 'Regra de comissão criada.');
    }
}
