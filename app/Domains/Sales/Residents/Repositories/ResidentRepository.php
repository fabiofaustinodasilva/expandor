<?php

namespace App\Domains\Sales\Residents\Repositories;

use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ResidentRepository
{
    public function paginateForProperty(Property $property, int $perPage = 15): LengthAwarePaginator
    {
        return Resident::query()
            ->where('property_id', $property->id)
            ->orderByDesc('is_primary_contact')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Property>
     */
    public function propertyOptions(): Collection
    {
        return Property::query()
            ->with('address:id,street,number,neighborhood')
            ->latest('id')
            ->get();
    }
}
