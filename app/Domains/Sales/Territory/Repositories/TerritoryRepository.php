<?php

namespace App\Domains\Sales\Territory\Repositories;

use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TerritoryRepository
{
    public function paginateCities(int $perPage = 15): LengthAwarePaginator
    {
        return City::query()
            ->withCount('sectors')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginateSectors(?int $cityId = null, int $perPage = 15): LengthAwarePaginator
    {
        return Sector::query()
            ->with('city:id,name,state')
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, City>
     */
    public function activeCities()
    {
        return City::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'state']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Sector>
     */
    public function activeSectors(?int $cityId = null)
    {
        return Sector::query()
            ->where('active', true)
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->orderBy('name')
            ->get(['id', 'city_id', 'name']);
    }
}
