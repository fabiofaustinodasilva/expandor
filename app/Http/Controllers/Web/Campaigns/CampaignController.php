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
use App\Domains\Geo\Repositories\GeoCatalogRepository;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Services\TerritoryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignService $campaigns,
        protected CampaignRepository $repository,
        protected GeoCatalogRepository $geo,
        protected TerritoryService $territory,
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

    public function municipalities(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $data = $request->validate([
            'uf' => ['required', 'string', 'size:2'],
            'q' => ['nullable', 'string', 'max:80'],
        ]);

        $items = $this->geo->municipalitiesForUf(
            $data['uf'],
            $data['q'] ?? null,
        )->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'ibge_code' => $m->ibge_code,
        ]);

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * Áreas (setores) da cidade operacional materializada a partir do município.
     */
    public function areasForMunicipality(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $data = $request->validate([
            'geo_municipality_id' => ['required', 'integer', 'min:1', 'exists:geo_municipalities,id'],
        ]);

        $city = $this->territory->upsertCityFromCatalog((int) $data['geo_municipality_id']);

        $areas = $this->repository->sectorOptions($city->id)->map(fn ($sector) => [
            'id' => $sector->id,
            'city_id' => $sector->city_id,
            'name' => $sector->name,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'city_id' => $city->id,
                'areas' => $areas,
            ],
        ]);
    }

    public function storeArea(Request $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'geo_municipality_id' => ['required', 'integer', 'min:1', 'exists:geo_municipalities,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $city = $this->territory->upsertCityFromCatalog((int) $data['geo_municipality_id']);
        $sector = $this->territory->upsertSector([
            'city_id' => $city->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'city_id' => $city->id,
                'area' => [
                    'id' => $sector->id,
                    'city_id' => $sector->city_id,
                    'name' => $sector->name,
                ],
            ],
        ], 201);
    }

    /**
     * @deprecated Prefer areasForMunicipality — mantido para compat.
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

        $campaign->load(['users:id', 'sectors', 'city.geoMunicipality.state']);

        return view('campaigns.edit', array_merge($this->formData($campaign), [
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
    protected function formData(?Campaign $campaign = null): array
    {
        $city = $campaign?->city;
        $geoMunicipalityId = old(
            'geo_municipality_id',
            $city?->geo_municipality_id
        );
        $selectedUf = old('geo_state_uf', $city?->state ?? $city?->geoMunicipality?->state?->uf);

        $initialAreas = collect();
        if ($city) {
            $initialAreas = $this->repository->sectorOptions($city->id);
        }

        return [
            'geoStates' => $this->geo->states(),
            'sellers' => $this->repository->sellerOptions($campaign),
            'statuses' => CampaignStatus::options(),
            'selectedUf' => $selectedUf ? strtoupper((string) $selectedUf) : '',
            'selectedGeoMunicipalityId' => $geoMunicipalityId ? (int) $geoMunicipalityId : null,
            'selectedGeoMunicipalityName' => $city?->geoMunicipality?->name ?? $city?->name,
            'initialAreas' => $initialAreas,
            'legacyCityId' => $city && ! $city->geo_municipality_id ? $city->id : null,
            'municipalitiesUrl' => route('campaigns.municipalities'),
            'areasForMunicipalityUrl' => route('campaigns.areas-for-municipality'),
            'storeAreaUrl' => route('campaigns.areas.store'),
        ];
    }
}
