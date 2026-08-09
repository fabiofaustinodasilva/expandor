<?php

namespace App\Http\Controllers\Web\Visits;

use App\Domains\Sales\Products\Services\ProductCatalogService;
use App\Domains\Sales\SaleFields\SaleFieldsPolicyResolver;
use App\Domains\Visits\Actions\CompleteFollowUpAction;
use App\Domains\Visits\Actions\ScheduleFollowUpAction;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Repositories\VisitRepository;
use App\Domains\Visits\Requests\CompleteFollowUpRequest;
use App\Domains\Visits\Requests\StoreFollowUpRequest;
use App\Http\Controllers\Controller;
use App\Support\CommercialTerminology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function __construct(
        protected VisitRepository $repository,
        protected ProductCatalogService $products,
        protected SaleFieldsPolicyResolver $saleFields,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Visit::class);

        /** @var \App\Domains\Company\Models\User $user */
        $user = auth()->user();
        $user->loadMissing('role');

        $teamView = ! $this->repository->scopesAgendaToOwnFollowUps($user);
        $salePolicy = $this->saleFields->resolveForUser($user);
        $dayFilter = $request->query('day') === 'today' ? 'today' : null;

        return view('visits.follow-ups.index', [
            'followUps' => $this->repository->paginatePendingFollowUps($user, 15, $dayFilter),
            'teamView' => $teamView,
            'dayFilter' => $dayFilter,
            'outcomeOptions' => CommercialTerminology::agendaOutcomeOptions(),
            'sellableProducts' => $this->products->sellableOptions(),
            'saleRequiredChecklist' => $salePolicy->checklist(),
            'saleRequiredFields' => $salePolicy->required(),
            'companyName' => $user->company?->name ?? 'Expandor',
        ]);
    }

    public function create(Visit $visit): View
    {
        $this->authorize('manageFollowUps', Visit::class);
        $this->authorize('view', $visit);

        $visit->load(['property.address', 'campaign:id,name']);

        return view('visits.follow-ups.create', [
            'visit' => $visit,
        ]);
    }

    public function store(
        StoreFollowUpRequest $request,
        Visit $visit,
        ScheduleFollowUpAction $action
    ): RedirectResponse {
        $this->authorize('manageFollowUps', Visit::class);
        $this->authorize('view', $visit);

        $action->execute($visit, $request->validated(), $request->user());

        return redirect()
            ->route('visits.show', $visit)
            ->with('success', 'Retorno agendado com sucesso.');
    }

    public function complete(
        CompleteFollowUpRequest $request,
        FollowUp $followUp,
        CompleteFollowUpAction $action
    ): RedirectResponse {
        $this->authorize('complete', $followUp);

        $result = $action->execute($followUp, $request->validated(), $request->user());

        $message = 'Retorno concluído e visita registrada.';
        if ($result['next_follow_up'] !== null) {
            $message = 'Retorno concluído. Novo retorno agendado para '.$result['next_follow_up']->scheduleLabel().'.';
        }

        return redirect()
            ->route('follow-ups.index')
            ->with('success', $message);
    }
}
