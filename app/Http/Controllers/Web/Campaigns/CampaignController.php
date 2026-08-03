<?php

namespace App\Http\Controllers\Web\Campaigns;

use App\Domains\Campaigns\Actions\ActivateCampaignAction;
use App\Domains\Campaigns\Actions\FinishCampaignAction;
use App\Domains\Campaigns\Actions\PauseCampaignAction;
use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Campaigns\Repositories\CampaignRepository;
use App\Domains\Campaigns\Requests\StoreCampaignRequest;
use App\Domains\Campaigns\Requests\UpdateCampaignRequest;
use App\Domains\Campaigns\Services\CampaignService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignService $campaigns,
        protected CampaignRepository $repository
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Campaign::class);

        return view('campaigns.index', [
            'campaigns' => $this->repository->paginate(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Campaign::class);

        return view('campaigns.create', $this->formData());
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $this->campaigns->create($request->validated());

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Campanha criada com sucesso.');
    }

    public function edit(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);

        $campaign->load(['users:id', 'sectors:id']);

        return view('campaigns.edit', array_merge($this->formData(), [
            'campaign' => $campaign,
        ]));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $this->campaigns->update($campaign, $request->validated());

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Campanha atualizada com sucesso.');
    }

    public function activate(Campaign $campaign, ActivateCampaignAction $action): RedirectResponse
    {
        $this->authorize('activate', $campaign);

        $action->execute($campaign);

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Campanha ativada.');
    }

    public function pause(Campaign $campaign, PauseCampaignAction $action): RedirectResponse
    {
        $this->authorize('pause', $campaign);

        $action->execute($campaign);

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Campanha pausada.');
    }

    public function finish(Campaign $campaign, FinishCampaignAction $action): RedirectResponse
    {
        $this->authorize('finish', $campaign);

        $action->execute($campaign);

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Campanha finalizada.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'cities' => $this->repository->cityOptions(),
            'sectors' => $this->repository->sectorOptions(),
            'sellers' => $this->repository->sellerOptions(),
            'statuses' => CampaignStatus::options(),
        ];
    }
}
