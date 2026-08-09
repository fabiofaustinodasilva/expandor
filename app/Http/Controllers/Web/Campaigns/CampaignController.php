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
use App\Domains\Sales\Territory\Models\City;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $cityId = old('city_id') ? (int) old('city_id') : null;

        return view('campaigns.create', $this->formData($cityId));
    }

    /**
     * Setores ativos da cidade (tenant) — carregamento dependente no formulário.
     */
    public function sectorsForCity(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $data = $request->validate([
            'city_id' => ['required', 'integer', 'min:1'],
        ]);

        $city = City::query()->findOrFail((int) $data['city_id']);

        $sectors = $this->repository->sectorOptions($city->id)->map(fn ($sector) => [
            'id' => $sector->id,
            'city_id' => $sector->city_id,
            'name' => $sector->name,
        ]);

        return response()->json([
            'success' => true,
            'data' => $sectors,
        ]);
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

        $cityId = old('city_id') ? (int) old('city_id') : (int) $campaign->city_id;

        return view('campaigns.edit', array_merge($this->formData($cityId), [
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
    protected function formData(?int $cityId = null): array
    {
        return [
            'cities' => $this->repository->cityOptions(),
            'sectors' => $cityId
                ? $this->repository->sectorOptions($cityId)
                : new \Illuminate\Database\Eloquent\Collection,
            'sellers' => $this->repository->sellerOptions(),
            'statuses' => CampaignStatus::options(),
            'sectorsForCityUrl' => route('campaigns.sectors-for-city'),
        ];
    }
}
