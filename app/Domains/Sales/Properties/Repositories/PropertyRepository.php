<?php

namespace App\Domains\Sales\Properties\Repositories;

use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PropertyRepository
{
    public function paginateAddresses(int $perPage = 15): LengthAwarePaginator
    {
        return Address::query()
            ->with(['city:id,name,state', 'sector:id,name'])
            ->withCount('properties')
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateProperties(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return Property::query()
            ->with(['address.city:id,name,state', 'address.sector:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Address>
     */
    public function addressOptions(): Collection
    {
        return Address::query()
            ->with('city:id,name,state')
            ->orderBy('street')
            ->get();
    }

    /**
     * @return Collection<int, City>
     */
    public function activeCities(): Collection
    {
        return City::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'state']);
    }

    /**
     * @return Collection<int, Sector>
     */
    public function sectorsForCity(?int $cityId = null): Collection
    {
        return Sector::query()
            ->where('active', true)
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->orderBy('name')
            ->get(['id', 'city_id', 'name']);
    }
}
