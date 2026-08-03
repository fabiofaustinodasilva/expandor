<?php

namespace App\Domains\Mobile\Services;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\SalesApp\Repositories\SalesAppRepository;
use App\Domains\SalesApp\Services\SalesAppService;
use App\Domains\Visits\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class MobileApiService
{
    public function __construct(
        protected SalesAppService $salesApp,
        protected SalesAppRepository $repository,
    ) {}

    /**
     * @return array{active_campaigns: int, visits_today: int, pending_follow_ups: int}
     */
    public function dashboard(User $seller): array
    {
        return $this->salesApp->dashboard($seller);
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function campaigns(User $seller): Collection
    {
        return $this->salesApp->myCampaigns($seller);
    }

    public function campaign(User $seller, int $campaignId): Campaign
    {
        return $this->salesApp->campaignForSeller($seller, $campaignId);
    }

    public function campaignProperties(User $seller, int $campaignId, int $perPage = 50): LengthAwarePaginator
    {
        $campaign = $this->salesApp->campaignForSeller($seller, $campaignId);

        return $this->repository->paginateCampaignProperties($campaign, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerVisit(User $seller, array $data): Visit
    {
        $campaign = $this->salesApp->campaignForSeller($seller, (int) $data['campaign_id']);
        $property = $this->salesApp->campaignProperty($campaign, (int) $data['property_id']);

        $visit = $this->salesApp->registerQuickVisit($seller, $campaign, $property, $data);

        if (! empty($data['schedule_follow_up']) && ! empty($data['follow_up_at'])) {
            $this->salesApp->scheduleFollowUp($seller, $visit, [
                'scheduled_at' => $data['follow_up_at'],
                'notes' => $data['follow_up_notes'] ?? null,
            ]);
        }

        return $visit->load(['property.address', 'campaign:id,name', 'followUps']);
    }

    /**
     * Offline sync contract — queue is empty until devices push local payloads.
     *
     * @return array{
     *     server_time: string,
     *     sync_token: string,
     *     pending_from_server: array{visits: list<mixed>, follow_ups: list<mixed>},
     *     accepted_client_ids: list<string>
     * }
     */
    public function pendingSync(User $seller): array
    {
        $followUps = $this->repository->paginateSellerFollowUps($seller, 100);

        return [
            'server_time' => now()->toIso8601String(),
            'sync_token' => (string) Str::uuid(),
            'pending_from_server' => [
                'visits' => [],
                'follow_ups' => $followUps->getCollection()->map(fn ($item) => [
                    'id' => $item->id,
                    'visit_id' => $item->visit_id,
                    'scheduled_at' => $item->scheduled_at?->toIso8601String(),
                    'status' => $item->status?->value ?? $item->status,
                    'notes' => $item->notes,
                ])->values()->all(),
            ],
            'accepted_client_ids' => [],
        ];
    }

    /**
     * Map markers for an assigned campaign (seller-scoped).
     *
     * @return list<array<string, mixed>>
     */
    public function campaignMarkers(User $seller, int $campaignId): array
    {
        $campaign = $this->salesApp->campaignForSeller($seller, $campaignId);
        $properties = $this->repository->paginateCampaignProperties($campaign, 500);

        return $properties->getCollection()->map(function (Property $property) {
            $resident = $property->residents->first();

            return [
                'id' => $property->id,
                'property_id' => $property->id,
                'latitude' => (float) $property->latitude,
                'longitude' => (float) $property->longitude,
                'status' => $property->status?->value ?? $property->status,
                'status_label' => method_exists($property->status, 'label')
                    ? $property->status->label()
                    : (string) $property->status,
                'address' => trim(implode(', ', array_filter([
                    $property->address?->street,
                    $property->address?->number,
                    $property->address?->neighborhood,
                ]))),
                'resident_name' => $resident?->name,
                'resident_phone' => $resident?->phone,
            ];
        })->values()->all();
    }
}
