<?php

namespace App\Http\Controllers\Web\Sales\Territory;

use App\Domains\Sales\Territory\Actions\ToggleSectorStatusAction;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Sales\Territory\Repositories\TerritoryRepository;
use App\Domains\Sales\Territory\Requests\StoreSectorRequest;
use App\Domains\Sales\Territory\Requests\UpdateSectorRequest;
use App\Domains\Sales\Territory\Services\TerritoryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectorController extends Controller
{
    public function __construct(
        protected TerritoryService $territory,
        protected TerritoryRepository $repository
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sector::class);

        $cityId = $request->integer('city_id') ?: null;

        return view('sales.territory.sectors.index', [
            'sectors' => $this->repository->paginateSectors($cityId),
            'cities' => $this->repository->activeCities(),
            'selectedCityId' => $cityId,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Sector::class);

        return view('sales.territory.sectors.create', [
            'cities' => $this->repository->activeCities(),
        ]);
    }

    public function store(StoreSectorRequest $request): RedirectResponse
    {
        $this->authorize('create', Sector::class);

        $this->territory->createSector($request->validated());

        return redirect()
            ->route('sectors.index')
            ->with('success', 'Setor criado com sucesso.');
    }

    public function edit(Sector $sector): View
    {
        $this->authorize('update', $sector);

        return view('sales.territory.sectors.edit', [
            'sector' => $sector,
            'cities' => $this->repository->activeCities(),
        ]);
    }

    public function update(UpdateSectorRequest $request, Sector $sector): RedirectResponse
    {
        $this->authorize('update', $sector);

        $this->territory->updateSector($sector, $request->validated());

        return redirect()
            ->route('sectors.index')
            ->with('success', 'Setor atualizado com sucesso.');
    }

    public function toggleStatus(Sector $sector, ToggleSectorStatusAction $action): RedirectResponse
    {
        $this->authorize('toggleStatus', $sector);

        $action->execute($sector);

        return redirect()
            ->route('sectors.index')
            ->with('success', 'Status do setor atualizado.');
    }
}
