<?php

namespace App\Http\Controllers\Web\CRM;

use App\Domains\CRM\Actions\LoseOpportunityAction;
use App\Domains\CRM\Actions\MoveOpportunityStageAction;
use App\Domains\CRM\Actions\WinOpportunityAction;
use App\Domains\CRM\Enums\OpportunityStatus;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Models\PipelineStage;
use App\Domains\CRM\Repositories\OpportunityRepository;
use App\Domains\CRM\Requests\LoseOpportunityRequest;
use App\Domains\CRM\Requests\MoveOpportunityRequest;
use App\Domains\CRM\Requests\StoreOpportunityRequest;
use App\Domains\CRM\Requests\UpdateOpportunityRequest;
use App\Domains\CRM\Services\OpportunityService;
use App\Domains\CRM\Services\PipelineService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function __construct(
        protected OpportunityService $opportunities,
        protected OpportunityRepository $repository,
        protected PipelineService $pipeline,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Opportunity::class);

        return view('crm.opportunities.index', [
            'opportunities' => $this->repository->paginate(
                status: $request->query('status'),
                ownerId: $request->integer('owner_id') ?: null,
            ),
            'statuses' => OpportunityStatus::options(),
        ]);
    }

    public function kanban(): View
    {
        $this->authorize('viewAny', Opportunity::class);

        $stages = $this->pipeline->stages();

        return view('crm.opportunities.kanban', [
            'stages' => $stages,
            'columns' => $this->repository->kanbanByStage(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Opportunity::class);

        return view('crm.opportunities.create', $this->formData());
    }

    public function store(StoreOpportunityRequest $request): RedirectResponse
    {
        $this->authorize('create', Opportunity::class);

        $this->opportunities->create($request->validated(), $request->user());

        return redirect()
            ->route('crm.opportunities.kanban')
            ->with('success', 'Oportunidade criada.');
    }

    public function edit(Opportunity $opportunity): View
    {
        $this->authorize('update', $opportunity);

        return view('crm.opportunities.edit', array_merge($this->formData(), [
            'opportunity' => $opportunity,
        ]));
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $this->authorize('update', $opportunity);

        $this->opportunities->update($opportunity, $request->validated(), $request->user());

        return redirect()
            ->route('crm.opportunities.kanban')
            ->with('success', 'Oportunidade atualizada.');
    }

    public function move(
        MoveOpportunityRequest $request,
        Opportunity $opportunity,
        MoveOpportunityStageAction $action,
    ): RedirectResponse {
        $this->authorize('move', $opportunity);

        $stage = PipelineStage::query()->findOrFail($request->validated('pipeline_stage_id'));
        $action->execute($opportunity, $stage, $request->user());

        return back()->with('success', 'Estágio atualizado.');
    }

    public function win(Opportunity $opportunity, WinOpportunityAction $action): RedirectResponse
    {
        $this->authorize('win', $opportunity);
        $action->execute($opportunity, request()->user());

        return back()->with('success', 'Oportunidade marcada como ganha.');
    }

    public function lose(
        LoseOpportunityRequest $request,
        Opportunity $opportunity,
        LoseOpportunityAction $action,
    ): RedirectResponse {
        $this->authorize('lose', $opportunity);
        $action->execute($opportunity, $request->validated(), $request->user());

        return back()->with('success', 'Oportunidade marcada como perdida.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'stages' => $this->pipeline->stages(),
            'sellers' => $this->repository->sellerOptions(),
            'statuses' => OpportunityStatus::options(),
        ];
    }
}
