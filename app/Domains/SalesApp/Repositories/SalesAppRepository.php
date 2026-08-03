<?php

namespace App\Domains\SalesApp\Repositories;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SalesAppRepository
{
    /**
     * Campaigns assigned to the seller.
     *
     * @return Collection<int, Campaign>
     */
    public function campaignsForSeller(User $seller, bool $activeOnly = false): Collection
    {
        return Campaign::query()
            ->whereHas('users', fn ($q) => $q->where('users.id', $seller->id))
            ->when(
                $activeOnly,
                fn ($q) => $q->where('status', CampaignStatus::ACTIVE),
                fn ($q) => $q->whereIn('status', [
                    CampaignStatus::ACTIVE,
                    CampaignStatus::PAUSED,
                ])
            )
            ->with(['city:id,name,state'])
            ->withCount(['visits' => fn ($q) => $q->where('user_id', $seller->id)])
            ->orderByDesc('start_date')
            ->get();
    }

    public function findAssignedCampaign(User $seller, int $campaignId): Campaign
    {
        return Campaign::query()
            ->whereHas('users', fn ($q) => $q->where('users.id', $seller->id))
            ->with(['city:id,name,state', 'sectors:id,name'])
            ->findOrFail($campaignId);
    }

    public function paginateCampaignProperties(Campaign $campaign, int $perPage = 20): LengthAwarePaginator
    {
        $sectorIds = $campaign->sectors()->pluck('sectors.id');

        return Property::query()
            ->with([
                'address:id,street,number,neighborhood,city_id,sector_id,latitude,longitude',
                'residents' => fn ($q) => $q
                    ->where('is_primary_contact', true)
                    ->limit(1),
            ])
            ->whereHas('address', function ($query) use ($campaign, $sectorIds): void {
                $query->where('city_id', $campaign->city_id);

                if ($sectorIds->isNotEmpty()) {
                    $query->whereIn('sector_id', $sectorIds);
                }
            })
            ->withCount([
                'visits as campaign_visits_count' => fn ($q) => $q->where('campaign_id', $campaign->id),
            ])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findCampaignProperty(Campaign $campaign, int $propertyId): Property
    {
        $sectorIds = $campaign->sectors()->pluck('sectors.id');

        return Property::query()
            ->with([
                'address.city:id,name,state',
                'address.sector:id,name',
                'residents' => fn ($q) => $q->orderByDesc('is_primary_contact')->limit(3),
            ])
            ->whereKey($propertyId)
            ->whereHas('address', function ($query) use ($campaign, $sectorIds): void {
                $query->where('city_id', $campaign->city_id);

                if ($sectorIds->isNotEmpty()) {
                    $query->whereIn('sector_id', $sectorIds);
                }
            })
            ->firstOrFail();
    }

    public function paginateSellerFollowUps(User $seller, int $perPage = 20): LengthAwarePaginator
    {
        return FollowUp::query()
            ->where('user_id', $seller->id)
            ->where('status', FollowUpStatus::PENDING)
            ->with([
                'visit.property.address:id,street,number,neighborhood',
                'visit.campaign:id,name',
            ])
            ->orderBy('scheduled_at')
            ->paginate($perPage);
    }

    /**
     * @return array{active_campaigns: int, visits_today: int, pending_follow_ups: int}
     */
    public function sellerDashboardStats(User $seller): array
    {
        return [
            'active_campaigns' => Campaign::query()
                ->where('status', CampaignStatus::ACTIVE)
                ->whereHas('users', fn ($q) => $q->where('users.id', $seller->id))
                ->count(),
            'visits_today' => Visit::query()
                ->where('user_id', $seller->id)
                ->whereDate('visited_at', now()->toDateString())
                ->count(),
            'pending_follow_ups' => FollowUp::query()
                ->where('user_id', $seller->id)
                ->where('status', FollowUpStatus::PENDING)
                ->count(),
        ];
    }

    public function sellerIsAssigned(User $seller, Campaign $campaign): bool
    {
        return DB::table('campaign_users')
            ->where('campaign_id', $campaign->id)
            ->where('user_id', $seller->id)
            ->exists();
    }
}
