<?php

namespace App\Domains\SalesApp\Services;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\SalesApp\Repositories\SalesAppRepository;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Services\VisitService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SalesAppService
{
    public function __construct(
        protected SalesAppRepository $repository,
        protected VisitService $visits,
    ) {}

    /**
     * @return array{active_campaigns: int, visits_today: int, pending_follow_ups: int}
     */
    public function dashboard(User $seller): array
    {
        return $this->repository->sellerDashboardStats($seller);
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function myCampaigns(User $seller): Collection
    {
        return $this->repository->campaignsForSeller($seller);
    }

    public function campaignForSeller(User $seller, int $campaignId): Campaign
    {
        return $this->repository->findAssignedCampaign($seller, $campaignId);
    }

    public function campaignProperties(Campaign $campaign): LengthAwarePaginator
    {
        return $this->repository->paginateCampaignProperties($campaign);
    }

    public function campaignProperty(Campaign $campaign, int $propertyId): Property
    {
        return $this->repository->findCampaignProperty($campaign, $propertyId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerQuickVisit(
        User $seller,
        Campaign $campaign,
        Property $property,
        array $data
    ): Visit {
        $this->assertAssigned($seller, $campaign);

        $payload = array_merge($data, [
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'visited_at' => $data['visited_at'] ?? now(),
        ]);

        return $this->visits->register($campaign, $payload, $seller);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function scheduleFollowUp(User $seller, Visit $visit, array $data): FollowUp
    {
        if ((int) $visit->user_id !== (int) $seller->id) {
            throw new AuthorizationException('Retorno permitido apenas para visitas do próprio vendedor.');
        }

        return $this->visits->scheduleFollowUp($visit, array_merge($data, [
            'user_id' => $seller->id,
        ]), $seller);
    }

    public function completeFollowUp(User $seller, FollowUp $followUp): FollowUp
    {
        if ((int) $followUp->user_id !== (int) $seller->id) {
            throw new AuthorizationException('Somente o responsável pode concluir este retorno.');
        }

        return $this->visits->completeFollowUp($followUp);
    }

    public function myFollowUps(User $seller): LengthAwarePaginator
    {
        return $this->repository->paginateSellerFollowUps($seller);
    }

    protected function assertAssigned(User $seller, Campaign $campaign): void
    {
        if (! $this->repository->sellerIsAssigned($seller, $campaign)) {
            throw ValidationException::withMessages([
                'campaign_id' => 'Você não está atribuído a esta campanha.',
            ]);
        }
    }
}
