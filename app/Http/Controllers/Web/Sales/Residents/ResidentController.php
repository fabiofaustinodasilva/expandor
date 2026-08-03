<?php

namespace App\Http\Controllers\Web\Sales\Residents;

use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Actions\ChangeResidentStatusAction;
use App\Domains\Sales\Residents\Enums\ResidentStatus;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Residents\Repositories\ResidentRepository;
use App\Domains\Sales\Residents\Requests\ChangeResidentStatusRequest;
use App\Domains\Sales\Residents\Requests\StoreResidentRequest;
use App\Domains\Sales\Residents\Requests\UpdateResidentRequest;
use App\Domains\Sales\Residents\Services\ResidentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function __construct(
        protected ResidentService $residents,
        protected ResidentRepository $repository
    ) {}

    public function index(Property $property): View
    {
        $this->authorize('viewAny', Resident::class);

        $property->load('address.city');

        return view('sales.residents.index', [
            'property' => $property,
            'residents' => $this->repository->paginateForProperty($property),
        ]);
    }

    public function create(Property $property): View
    {
        $this->authorize('create', Resident::class);

        $property->load('address.city');

        return view('sales.residents.create', [
            'property' => $property,
            'statuses' => ResidentStatus::options(),
        ]);
    }

    public function store(StoreResidentRequest $request, Property $property): RedirectResponse
    {
        $this->authorize('create', Resident::class);

        $this->residents->create($property, $request->validated());

        return redirect()
            ->route('properties.residents.index', $property)
            ->with('success', 'Morador cadastrado com sucesso.');
    }

    public function edit(Resident $resident): View
    {
        $this->authorize('update', $resident);

        $resident->load(['property.address.city', 'histories']);

        return view('sales.residents.edit', [
            'resident' => $resident,
            'property' => $resident->property,
        ]);
    }

    public function update(UpdateResidentRequest $request, Resident $resident): RedirectResponse
    {
        $this->authorize('update', $resident);

        $this->residents->update($resident, $request->validated());

        return redirect()
            ->route('properties.residents.index', $resident->property_id)
            ->with('success', 'Morador atualizado com sucesso.');
    }

    public function editStatus(Resident $resident): View
    {
        $this->authorize('changeStatus', $resident);

        $resident->load(['property.address.city', 'histories']);

        return view('sales.residents.status', [
            'resident' => $resident,
            'statuses' => ResidentStatus::options(),
        ]);
    }

    public function updateStatus(
        ChangeResidentStatusRequest $request,
        Resident $resident,
        ChangeResidentStatusAction $action
    ): RedirectResponse {
        $this->authorize('changeStatus', $resident);

        $action->execute($resident, $request->validated());

        return redirect()
            ->route('properties.residents.index', $resident->property_id)
            ->with('success', 'Status do morador atualizado.');
    }
}
