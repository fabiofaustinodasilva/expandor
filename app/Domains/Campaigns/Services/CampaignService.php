<?php

namespace App\Domains\Campaigns\Services;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Sales\Territory\Services\TerritoryService;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignService
{
    public function __construct(
        protected TerritoryService $territory,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Campaign
    {
        return DB::transaction(function () use ($data) {
            /** @var TenantContext $context */
            $context = app(TenantContext::class);

            $cityId = $this->resolveOperationalCityId($data);

            $campaign = Campaign::query()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'city_id' => $cityId,
                'status' => CampaignStatus::from($data['status'] ?? CampaignStatus::DRAFT->value),
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'goal_visits' => (int) ($data['goal_visits'] ?? 0),
                'created_by' => $context->user()?->id,
            ]);

            $this->syncAssociations($campaign, $data);

            return $campaign->load(['users', 'sectors', 'city']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Campaign $campaign, array $data): Campaign
    {
        return DB::transaction(function () use ($campaign, $data) {
            $cityId = $this->resolveOperationalCityId($data);

            $campaign->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'city_id' => $cityId,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'goal_visits' => (int) ($data['goal_visits'] ?? 0),
            ]);

            $this->syncAssociations($campaign, $data);

            return $campaign->refresh()->load(['users', 'sectors', 'city']);
        });
    }

    public function activate(Campaign $campaign): Campaign
    {
        return $this->changeStatus($campaign, CampaignStatus::ACTIVE);
    }

    public function pause(Campaign $campaign): Campaign
    {
        return $this->changeStatus($campaign, CampaignStatus::PAUSED);
    }

    public function finish(Campaign $campaign): Campaign
    {
        return $this->changeStatus($campaign, CampaignStatus::FINISHED);
    }

    public function changeStatus(Campaign $campaign, CampaignStatus $status): Campaign
    {
        if ($campaign->status === $status) {
            return $campaign;
        }

        $this->assertTransitionAllowed($campaign->status, $status);

        $campaign->update(['status' => $status]);

        return $campaign->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveOperationalCityId(array $data): int
    {
        if (! empty($data['geo_municipality_id'])) {
            return $this->territory
                ->upsertCityFromCatalog((int) $data['geo_municipality_id'])
                ->id;
        }

        if (! empty($data['city_id'])) {
            return (int) $data['city_id'];
        }

        throw ValidationException::withMessages([
            'geo_municipality_id' => 'Selecione a cidade da campanha.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncAssociations(Campaign $campaign, array $data): void
    {
        $userIds = collect($data['user_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $sectorIds = collect($data['sector_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($userIds !== []) {
            $validUserIds = User::query()
                ->whereIn('id', $userIds)
                ->pluck('id')
                ->all();

            if (count($validUserIds) !== count($userIds)) {
                throw ValidationException::withMessages([
                    'user_ids' => 'Um ou mais vendedores selecionados são inválidos.',
                ]);
            }
        } else {
            $validUserIds = [];
        }

        if ($sectorIds !== []) {
            $sectors = Sector::query()
                ->where('city_id', $campaign->city_id)
                ->whereIn('id', $sectorIds)
                ->get(['id', 'name']);

            if ($sectors->count() !== count($sectorIds)) {
                throw ValidationException::withMessages([
                    'sector_ids' => 'As áreas devem pertencer à cidade da campanha.',
                ]);
            }

            $validSectorIds = $sectors
                ->reject(fn (Sector $sector) => $this->territory->isReservedWholeCitySectorName($sector->name))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } else {
            $validSectorIds = [];
        }

        $campaign->users()->sync($validUserIds);
        $campaign->sectors()->sync($validSectorIds);
    }

    protected function assertTransitionAllowed(CampaignStatus $from, CampaignStatus $to): void
    {
        $allowed = match ($from) {
            CampaignStatus::DRAFT => [CampaignStatus::ACTIVE, CampaignStatus::FINISHED],
            CampaignStatus::ACTIVE => [CampaignStatus::PAUSED, CampaignStatus::FINISHED],
            CampaignStatus::PAUSED => [CampaignStatus::ACTIVE, CampaignStatus::FINISHED],
            CampaignStatus::FINISHED => [],
        };

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Não é possível alterar o status de %s para %s.',
                    $from->label(),
                    $to->label()
                ),
            ]);
        }
    }
}
