<?php

namespace App\Http\Controllers\Web\Visits;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Visits\Actions\RegisterVisitAction;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Repositories\VisitRepository;
use App\Domains\Visits\Requests\StoreVisitRequest;
use App\Http\Controllers\Controller;
use App\Support\CommercialTerminology;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function __construct(
        protected VisitRepository $repository
    ) {}

    public function index(Campaign $campaign): View
    {
        $this->authorize('viewAny', Visit::class);

        $campaign->load('city:id,name,state');

        return view('visits.index', [
            'campaign' => $campaign,
            'visits' => $this->repository->paginateForCampaign($campaign),
        ]);
    }

    public function create(Campaign $campaign): View
    {
        $this->authorize('create', Visit::class);

        $campaign->load(['city:id,name,state', 'sectors:id']);

        return view('visits.create', [
            'campaign' => $campaign,
            'properties' => $this->repository->propertyOptionsForCampaign($campaign),
            'statuses' => CommercialTerminology::visitStatusOptions(),
        ]);
    }

    public function store(
        StoreVisitRequest $request,
        Campaign $campaign,
        RegisterVisitAction $action
    ): RedirectResponse {
        $this->authorize('create', Visit::class);

        $visit = $action->execute($campaign, $request->validated(), $request->user());

        return redirect()
            ->route('visits.show', $visit)
            ->with('success', 'Visita registrada com sucesso.');
    }

    public function show(Visit $visit): View
    {
        $this->authorize('view', $visit);

        $visit->load([
            'campaign.city:id,name,state',
            'property.address.city:id,name,state',
            'property.histories' => fn ($q) => $q->latest('created_at')->limit(10),
            'user:id,name',
            'followUps.user:id,name',
        ]);

        return view('visits.show', [
            'visit' => $visit,
        ]);
    }
}
