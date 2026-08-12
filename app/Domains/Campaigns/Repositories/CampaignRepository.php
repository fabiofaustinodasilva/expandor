<?php

namespace App\Domains\Campaigns\Repositories;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CampaignRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Campaign::query()
            ->with(['city:id,name,state', 'creator:id,name'])
            ->withCount(['users', 'sectors'])
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, City>
     */
    public function cityOptions(): Collection
    {
        return City::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'state']);
    }

    /**
     * @return Collection<int, Sector>
     */
    public function sectorOptions(?int $cityId = null): Collection
    {
        $territory = app(\App\Domains\Sales\Territory\Services\TerritoryService::class);

        return Sector::query()
            ->where('active', true)
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->orderBy('name')
            ->get(['id', 'city_id', 'name'])
            ->reject(fn (Sector $sector) => $territory->isReservedWholeCitySectorName($sector->name))
            ->values();
    }

    /**
     * Active sellers available for campaign assignment.
     * Inclui vendedores já vinculados mesmo se inativos (evita wipe silencioso no update).
     *
     * @return Collection<int, User>
     */
    public function sellerOptions(?Campaign $campaign = null): Collection
    {
        $assignedIds = $campaign
            ? $campaign->users()->pluck('users.id')->map(fn ($id) => (int) $id)->all()
            : [];

        return User::query()
            ->with('role:id,name,slug')
            ->where(function ($query) use ($assignedIds) {
                $query->where('status', User::STATUS_ACTIVE);
                if ($assignedIds !== []) {
                    $query->orWhereIn('id', $assignedIds);
                }
            })
            ->whereHas('role', fn ($q) => $q->whereIn('slug', [
                Role::SELLER,
                Role::SUPERVISOR,
                Role::MANAGER,
            ]))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role_id', 'status']);
    }
}
