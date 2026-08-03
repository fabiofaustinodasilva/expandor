<?php

namespace App\Http\Controllers\Web\Sales\Territory;

use App\Domains\Sales\Territory\Actions\ToggleCityStatusAction;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Repositories\TerritoryRepository;
use App\Domains\Sales\Territory\Requests\StoreCityRequest;
use App\Domains\Sales\Territory\Requests\UpdateCityRequest;
use App\Domains\Sales\Territory\Services\TerritoryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CityController extends Controller
{
    public function __construct(
        protected TerritoryService $territory,
        protected TerritoryRepository $repository
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', City::class);

        return view('sales.territory.cities.index', [
            'cities' => $this->repository->paginateCities(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', City::class);

        return view('sales.territory.cities.create');
    }

    public function store(StoreCityRequest $request): RedirectResponse
    {
        $this->authorize('create', City::class);

        $this->territory->createCity($request->validated());

        return redirect()
            ->route('cities.index')
            ->with('success', 'Cidade criada com sucesso.');
    }

    public function edit(City $city): View
    {
        $this->authorize('update', $city);

        return view('sales.territory.cities.edit', compact('city'));
    }

    public function update(UpdateCityRequest $request, City $city): RedirectResponse
    {
        $this->authorize('update', $city);

        $this->territory->updateCity($city, $request->validated());

        return redirect()
            ->route('cities.index')
            ->with('success', 'Cidade atualizada com sucesso.');
    }

    public function toggleStatus(City $city, ToggleCityStatusAction $action): RedirectResponse
    {
        $this->authorize('toggleStatus', $city);

        $action->execute($city);

        return redirect()
            ->route('cities.index')
            ->with('success', 'Status da cidade atualizado.');
    }
}
