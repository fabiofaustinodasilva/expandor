<?php

namespace App\Http\Controllers\Web\Sales\Properties;

use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Repositories\PropertyRepository;
use App\Domains\Sales\Properties\Requests\StoreAddressRequest;
use App\Domains\Sales\Properties\Requests\UpdateAddressRequest;
use App\Domains\Sales\Properties\Services\PropertyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function __construct(
        protected PropertyService $properties,
        protected PropertyRepository $repository
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Address::class);

        return view('sales.properties.addresses.index', [
            'addresses' => $this->repository->paginateAddresses(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Address::class);

        return view('sales.properties.addresses.create', [
            'cities' => $this->repository->activeCities(),
            'sectors' => $this->repository->sectorsForCity(),
        ]);
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $this->authorize('create', Address::class);

        $this->properties->createAddress($request->validated());

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Endereço cadastrado com sucesso.');
    }

    public function edit(Address $address): View
    {
        $this->authorize('update', $address);

        return view('sales.properties.addresses.edit', [
            'address' => $address,
            'cities' => $this->repository->activeCities(),
            'sectors' => $this->repository->sectorsForCity($address->city_id),
        ]);
    }

    public function update(UpdateAddressRequest $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $this->properties->updateAddress($address, $request->validated());

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Endereço atualizado com sucesso.');
    }
}
