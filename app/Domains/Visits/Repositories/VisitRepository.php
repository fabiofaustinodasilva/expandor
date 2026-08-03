<?php

namespace App\Domains\Visits\Repositories;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class VisitRepository
{
    public function paginateForCampaign(Campaign $campaign, int $perPage = 15): LengthAwarePaginator
    {
        return Visit::query()
            ->where('campaign_id', $campaign->id)
            ->with([
                'property.address:id,street,number,neighborhood',
                'user:id,name',
            ])
            ->latest('visited_at')
            ->paginate($perPage);
    }

    /**
     * Agenda de retornos pendentes.
     * Seller: somente os próprios. Manager/Admin/Supervisor: toda a equipe.
     */
    public function paginatePendingFollowUps(?User $viewer = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = FollowUp::query()
            ->where('status', FollowUpStatus::PENDING)
            ->with([
                'visit.property.address:id,street,number,neighborhood,city_id',
                'visit.property.residents',
                'visit.campaign:id,name',
                'user:id,name',
            ])
            ->orderBy('scheduled_at');

        if ($viewer !== null && $this->scopesAgendaToOwnFollowUps($viewer)) {
            $query->where('user_id', $viewer->id);
        }

        return $query->paginate($perPage);
    }

    public function scopesAgendaToOwnFollowUps(User $user): bool
    {
        $slug = $user->role?->slug;

        return ! in_array($slug, [
            Role::ADMINISTRATOR,
            Role::MANAGER,
            Role::SUPERVISOR,
        ], true);
    }

    /**
     * Histórico comercial do vendedor (Sprint 4.3).
     */
    public function paginateMyVisits(User $user, int $perPage = 30): LengthAwarePaginator
    {
        return Visit::query()
            ->with([
                'property.address',
                'property.residents',
                'campaign:id,name',
                'user:id,name',
                'followUps' => fn ($q) => $q
                    ->where('status', FollowUpStatus::PENDING)
                    ->orderBy('scheduled_at'),
            ])
            ->where('user_id', $user->id)
            ->orderByDesc('visited_at')
            ->paginate($perPage);
    }

    /**
     * FollowUps pendentes do vendedor por property_id (fallback da próxima ação).
     *
     * @param  array<int, int|null>  $propertyIds
     * @return SupportCollection<int, SupportCollection<int, FollowUp>>
     */
    public function pendingFollowUpsByPropertyForUser(User $user, array $propertyIds): SupportCollection
    {
        $propertyIds = array_values(array_unique(array_filter($propertyIds)));

        if ($propertyIds === []) {
            return collect();
        }

        return FollowUp::query()
            ->where('status', FollowUpStatus::PENDING)
            ->where('user_id', $user->id)
            ->whereHas('visit', fn ($q) => $q->whereIn('property_id', $propertyIds))
            ->with(['visit:id,property_id'])
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn (FollowUp $fu) => (int) $fu->visit?->property_id);
    }

    /**
     * Properties available for a campaign (same company, optionally city sectors).
     *
     * @return Collection<int, Property>
     */
    public function propertyOptionsForCampaign(Campaign $campaign): Collection
    {
        $sectorIds = $campaign->sectors()->pluck('sectors.id');

        return Property::query()
            ->with('address:id,street,number,neighborhood,city_id,sector_id')
            ->whereHas('address', function ($query) use ($campaign, $sectorIds): void {
                $query->where('city_id', $campaign->city_id);

                if ($sectorIds->isNotEmpty()) {
                    $query->whereIn('sector_id', $sectorIds);
                }
            })
            ->latest('id')
            ->limit(200)
            ->get();
    }
}
