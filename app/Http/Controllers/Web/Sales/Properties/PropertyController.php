<?php

namespace App\Http\Controllers\Web\Sales\Properties;

use App\Domains\Sales\Properties\Actions\ChangePropertyStatusAction;
use App\Domains\Sales\Properties\Enums\PropertyType;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Repositories\PropertyRepository;
use App\Domains\Sales\Properties\Requests\ChangePropertyStatusRequest;
use App\Domains\Sales\Properties\Requests\StorePropertyRequest;
use App\Domains\Sales\Properties\Services\PropertyService;
use App\Http\Controllers\Controller;
use App\Support\CommercialTerminology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function __construct(
        protected PropertyService $properties,
        protected PropertyRepository $repository
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Property::class);

        $status = $request->string('status')->toString() ?: null;

        return view('sales.properties.properties.index', [
            'properties' => $this->repository->paginateProperties($status),
            'statuses' => CommercialTerminology::propertyStatusOptions(),
            'selectedStatus' => $status,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Property::class);

        return view('sales.properties.properties.create', [
            'addresses' => $this->repository->addressOptions(),
            'types' => PropertyType::options(),
            'statuses' => CommercialTerminology::propertyStatusOptions(),
        ]);
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $this->authorize('create', Property::class);

        $this->properties->createProperty($request->validated(), $request->user());

        return redirect()
            ->route('properties.index')
            ->with('success', 'Cliente / ponto cadastrado com sucesso.');
    }

    public function editStatus(Property $property): View
    {
        $this->authorize('changeStatus', $property);

        $property->load(['address.city', 'histories.user']);

        return view('sales.properties.properties.status', [
            'property' => $property,
            'statuses' => CommercialTerminology::propertyStatusOptions(),
        ]);
    }

    public function updateStatus(
        ChangePropertyStatusRequest $request,
        Property $property,
        ChangePropertyStatusAction $action
    ): RedirectResponse {
        $this->authorize('changeStatus', $property);

        $action->execute($property, $request->validated(), $request->user());

        return redirect()
            ->route('properties.index')
            ->with('success', 'Status do imóvel atualizado.');
    }
}
