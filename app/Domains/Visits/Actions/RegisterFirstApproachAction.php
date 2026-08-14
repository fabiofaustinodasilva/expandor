<?php

namespace App\Domains\Visits\Actions;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Enums\PropertyType;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Services\PropertyService;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Residents\Services\ResidentService;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Security\Services\SecurityService;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Services\VisitService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fluxo porta a porta: ponto + primeira visita + resultado em uma transação.
 * Contrato reutilizável futuramente pelo app mobile.
 */
class RegisterFirstApproachAction
{
    public const NO_CAMPAIGN_MESSAGE = 'Você não possui uma campanha ativa. Solicite ao gestor a atribuição de uma campanha.';

    public function __construct(
        protected PropertyService $properties,
        protected VisitService $visits,
        protected ResidentService $residents,
        protected SecurityService $security,
    ) {}

    /**
     * Campanhas ativas atribuídas ao vendedor (tenancy via relação).
     *
     * @return Collection<int, Campaign>
     */
    public function activeCampaignsFor(User $user): Collection
    {
        return $user->campaigns()
            ->where('campaigns.status', CampaignStatus::ACTIVE->value)
            ->orderBy('campaigns.name')
            ->get();
    }

    /**
     * Resolve campanha: id explícito, ou única ativa do usuário.
     *
     * @throws ValidationException
     */
    public function resolveCampaign(User $user, mixed $campaignId): Campaign
    {
        $active = $this->activeCampaignsFor($user);

        if ($active->isEmpty()) {
            throw ValidationException::withMessages([
                'campaign_id' => self::NO_CAMPAIGN_MESSAGE,
            ]);
        }

        if ($campaignId !== null && $campaignId !== '') {
            $campaign = $active->firstWhere('id', (int) $campaignId);
            if ($campaign === null) {
                throw ValidationException::withMessages([
                    'campaign_id' => 'Selecione uma campanha ativa atribuída a você.',
                ]);
            }

            return $campaign;
        }

        if ($active->count() === 1) {
            return $active->first();
        }

        throw ValidationException::withMessages([
            'campaign_id' => 'Selecione a campanha deste atendimento.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{property: Property, visit: Visit}
     */
    public function execute(array $data, User $actor): array
    {
        $campaign = $this->resolveCampaign($actor, $data['campaign_id'] ?? null);
        $visitStatus = VisitStatus::from($data['status']);

        return DB::transaction(function () use ($data, $actor, $campaign, $visitStatus) {
            $accuracyNote = $this->gpsAccuracyNote($data['gps_accuracy'] ?? null);
            [$cityId, $sectorId] = $this->resolveTerritoryForCampaign($campaign, $data);

            $address = $this->properties->createAddress([
                'city_id' => $cityId,
                'sector_id' => $sectorId,
                'street' => $data['street'],
                'number' => $data['number'] ?? null,
                'neighborhood' => $data['neighborhood'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);

            $property = $this->properties->createProperty([
                'address_id' => $address->id,
                'type' => PropertyType::HOUSE->value,
                'status' => PropertyStatus::NEW->value,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'notes' => null,
                'history_description' => 'Ponto adicionado no mapa (primeiro atendimento).'.$accuracyNote,
            ], $actor);

            $property->forceFill([
                'created_by' => $actor->id,
            ])->save();

            if ((! empty($data['contact_name']) || ! empty($data['contact_phone']))
                && $actor->can('create', Resident::class)) {
                $this->residents->create($property, [
                    'name' => filled($data['contact_name'] ?? null) ? $data['contact_name'] : 'Contato',
                    'phone' => $data['contact_phone'] ?? null,
                    'is_primary_contact' => true,
                ]);
            }

            $this->security->recordAudit(
                action: 'point.created',
                user: $actor,
                auditable: $property,
                newValues: [
                    'property_id' => $property->id,
                    'status' => PropertyStatus::NEW->value,
                    'latitude' => (float) $property->latitude,
                    'longitude' => (float) $property->longitude,
                    'gps_accuracy' => $data['gps_accuracy'] ?? null,
                    'first_approach' => true,
                ],
            );

            $visit = $this->visits->register($campaign, [
                'property_id' => $property->id,
                'status' => $visitStatus->value,
                'notes' => $data['notes'] ?? null,
                'plan' => $data['plan'] ?? null,
                'product_id' => $data['product_id'] ?? null,
                'items' => $data['items'] ?? null,
                'customer_name' => $data['customer_name'] ?? $data['contact_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? $data['contact_phone'] ?? null,
                'customer_whatsapp' => $data['customer_whatsapp'] ?? null,
                'customer_document' => $data['customer_document'] ?? null,
                'customer_rg' => $data['customer_rg'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_birth_date' => $data['customer_birth_date'] ?? null,
                'due_day' => $data['due_day'] ?? null,
                'install_street' => $data['install_street'] ?? null,
                'install_number' => $data['install_number'] ?? null,
                'install_neighborhood' => $data['install_neighborhood'] ?? null,
                'install_reference' => $data['install_reference'] ?? null,
                'install_city' => $data['install_city'] ?? null,
                'sale_notes' => $data['sale_notes'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'visited_at' => $data['visited_at'] ?? now(),
                'user_id' => $actor->id,
                'first_approach' => true,
            ], $actor);

            // Sprint 8.2.16 — status Retorno sem FollowUp era “esquecível”.
            // StoreFirstApproachRequest já exige follow_up_at; aqui reforçamos o vínculo Agenda.
            if ($visitStatus === VisitStatus::RETURN_LATER) {
                if (empty($data['follow_up_at'])) {
                    throw ValidationException::withMessages([
                        'follow_up_at' => 'Informe a data do retorno.',
                    ]);
                }

                $this->visits->scheduleFollowUp($visit, [
                    'scheduled_at' => $data['follow_up_at'],
                    'notes' => $data['notes'] ?? null,
                ], $actor);
            }

            $property->refresh()->load(['address', 'residents']);

            return [
                'property' => $property,
                'visit' => $visit->fresh(['campaign', 'user']),
            ];
        });
    }

    protected function gpsAccuracyNote(mixed $accuracy): string
    {
        if ($accuracy === null || $accuracy === '') {
            return '';
        }

        $meters = (int) round((float) $accuracy);
        $note = ' Precisão GPS: '.$meters.'m.';
        if ($meters > 50) {
            $note .= ' Baixa precisão.';
        } elseif ($meters > 10) {
            $note .= ' Boa precisão.';
        } else {
            $note .= ' Alta precisão.';
        }

        return $note;
    }

    /**
     * Territory of the active campaign is the source of truth for the pin.
     * Request city/sector may come from a leftover filter or the first <option>.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: int, 1: int|null}
     */
    protected function resolveTerritoryForCampaign(Campaign $campaign, array $data): array
    {
        $cityId = (int) $campaign->city_id;
        $requestedSector = isset($data['sector_id']) && $data['sector_id'] !== '' && $data['sector_id'] !== null
            ? (int) $data['sector_id']
            : null;

        $campaign->loadMissing('sectors');
        $allowed = $campaign->sectors
            ->filter(fn (Sector $sector) => (int) $sector->city_id === $cityId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($requestedSector !== null && ($allowed->isEmpty() || $allowed->contains($requestedSector))) {
            $sector = Sector::query()->find($requestedSector);
            if ($sector !== null && (int) $sector->city_id === $cityId && (int) $sector->company_id === (int) $campaign->company_id) {
                return [$cityId, $requestedSector];
            }
        }

        return [$cityId, null];
    }
}
