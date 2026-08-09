<?php

namespace App\Domains\Geo\Repositories;

use App\Domains\Geo\Models\GeoMunicipality;
use App\Domains\Geo\Models\GeoState;
use Illuminate\Database\Eloquent\Collection;

class GeoCatalogRepository
{
    /**
     * @return Collection<int, GeoState>
     */
    public function states(): Collection
    {
        return GeoState::query()
            ->orderBy('name')
            ->get(['id', 'uf', 'name']);
    }

    /**
     * @return Collection<int, GeoMunicipality>
     */
    public function municipalitiesForUf(string $uf, ?string $search = null, int $limit = 80): Collection
    {
        $uf = strtoupper(trim($uf));
        $search = $search !== null ? trim($search) : null;

        return GeoMunicipality::query()
            ->whereHas('state', fn ($q) => $q->where('uf', $uf))
            ->when(
                $search !== null && $search !== '',
                fn ($q) => $q->where('name', 'like', '%'.$search.'%')
            )
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'geo_state_id', 'ibge_code', 'name']);
    }

    public function findMunicipality(int $id): ?GeoMunicipality
    {
        return GeoMunicipality::query()->with('state:id,uf,name')->find($id);
    }
}
