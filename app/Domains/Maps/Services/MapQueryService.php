<?php

namespace App\Domains\Maps\Services;

use App\Domains\Maps\DTOs\MapFiltersDTO;
use App\Domains\Maps\DTOs\MapMarkerDTO;
use App\Domains\Maps\Enums\MapCommercialGroup;
use App\Domains\Maps\Enums\MapMarkerColor;
use App\Domains\Maps\Repositories\MapRepository;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Models\PropertyHistory;
use App\Domains\Sales\Residents\Enums\ResidentStatus;
use App\Domains\Sales\Residents\Models\Resident;
use App\Support\CommercialTerminology;
use Illuminate\Support\Collection;

class MapQueryService
{
    public function __construct(
        protected MapRepository $repository,
        protected \App\Domains\Company\Support\FieldOps\FieldOpsPolicyResolver $fieldOps,
    ) {}

    /**
     * Aplica política da empresa (e futuro override por campanha) aos filtros do mapa.
     */
    public function constrainForUser(
        MapFiltersDTO $filters,
        \App\Domains\Company\Models\User $user,
        ?int $campaignId = null,
    ): MapFiltersDTO {
        $policy = $this->fieldOps->resolveForUser($user, $campaignId);
        $creatorIds = $this->fieldOps->visibleCreatorIds($user, $policy);

        $filters = $filters->withFieldOpsConstraints(
            creatorIds: $creatorIds,
            forcedStatuses: $policy->display->forcedStatusValues(),
            allowUiFilters: $policy->display->allowsUiFilters(),
        );

        if (! $filters->hasTextSearch()) {
            return $filters;
        }

        // Seller: só próprios (criados ou visitados). Manager/Admin/Supervisor: empresa.
        $sellerOwnerId = $user->role?->slug === Role::SELLER
            ? (int) $user->id
            : null;

        return $filters->forTextSearch($sellerOwnerId);
    }

    /**
     * @return list<MapMarkerDTO>
     */
    public function markers(MapFiltersDTO $filters): array
    {
        $properties = $this->repository->findGeolocatedProperties($filters);
        $kinds = $this->locationKinds($properties->pluck('id'));

        return $properties
            ->map(fn (Property $property) => $this->toMarker(
                $property,
                $kinds[(int) $property->id] ?? 'gps'
            ))
            ->values()
            ->all();
    }

    /**
     * Commercial summary for the current viewport/filters (UI only).
     *
     * @return array{
     *     total: int,
     *     customer: int,
     *     interested: int,
     *     visited: int,
     *     new: int,
     *     opportunity: string,
     *     opportunity_label: string
     * }
     */
    public function summary(MapFiltersDTO $filters): array
    {
        $byStatus = $this->repository->countByStatus($filters);
        $counts = [
            'customer' => 0,
            'interested' => 0,
            'visited' => 0,
            'new' => 0,
        ];

        foreach ($byStatus as $status => $total) {
            $group = MapCommercialGroup::fromStatus((string) $status)->value;
            $counts[$group] = ($counts[$group] ?? 0) + (int) $total;
        }

        $total = array_sum($counts);
        $opportunity = $this->opportunityLevel($counts['customer'], $total);

        return [
            'total' => $total,
            'customer' => $counts['customer'],
            'interested' => $counts['interested'],
            'visited' => $counts['visited'],
            'new' => $counts['new'],
            'opportunity' => $opportunity,
            'opportunity_label' => match ($opportunity) {
                'high' => 'Oportunidade alta',
                'low' => 'Baixa oportunidade',
                'medium' => 'Oportunidade média',
                default => 'Sem dados na região',
            },
        ];
    }

    protected function toMarker(Property $property, string $locationKind = 'gps'): MapMarkerDTO
    {
        $status = $property->status instanceof PropertyStatus
            ? $property->status
            : PropertyStatus::from((string) $property->getAttribute('status'));

        $resident = $this->resolveResident($property);
        $group = MapCommercialGroup::fromStatus($status);
        $address = $property->address;
        $addressLabel = $address?->label() ?? '';
        if ($address?->relationLoaded('city') && $address->city?->name) {
            $addressLabel = trim($addressLabel.', '.$address->city->name, ', ');
        }

        return new MapMarkerDTO(
            id: $property->id,
            latitude: (float) $property->latitude,
            longitude: (float) $property->longitude,
            status: $status->value,
            status_label: CommercialTerminology::propertyStatusLabel($status),
            color: MapMarkerColor::forStatus($status)->value,
            property_id: $property->id,
            address: $addressLabel,
            resident_name: $resident?->name,
            resident_phone: $resident?->phone,
            resident_whatsapp: $resident?->whatsapp,
            resident_document: $resident?->document,
            updated_at: $property->updated_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            location_kind: $locationKind,
            commercial_group: $group->value,
            owner_user_id: $property->created_by ? (int) $property->created_by : null,
            sold_product: null,
        );
    }

    /**
     * Lightweight UI heuristic — not a scoring engine.
     */
    protected function opportunityLevel(int $customers, int $total): string
    {
        if ($total === 0) {
            return 'unknown';
        }

        $ratio = $customers / $total;

        if ($ratio < 0.15) {
            return 'high';
        }

        if ($ratio > 0.40) {
            return 'low';
        }

        return 'medium';
    }

    /**
     * @param  Collection<int, int|string>  $propertyIds
     * @return array<int, string>
     */
    protected function locationKinds(Collection $propertyIds): array
    {
        $ids = $propertyIds->map(fn ($id) => (int) $id)->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        $kinds = array_fill_keys($ids, 'gps');

        $histories = PropertyHistory::query()
            ->whereIn('property_id', $ids)
            ->where(function ($query): void {
                $query->where('description', 'like', '%Localização ajustada%')
                    ->orWhere('description', 'like', '%Baixa precisão%')
                    ->orWhere('description', 'like', '%Precisão GPS:%');
            })
            ->orderBy('id')
            ->get(['property_id', 'description']);

        foreach ($histories->groupBy('property_id') as $propertyId => $rows) {
            $text = $rows->pluck('description')->implode(' ');
            if (str_contains($text, 'Localização ajustada')) {
                $kinds[(int) $propertyId] = 'adjusted';
            } elseif (str_contains($text, 'Baixa precisão')) {
                $kinds[(int) $propertyId] = 'low_accuracy';
            } elseif (preg_match('/Precisão GPS:\s*(\d+)/u', $text, $matches) && (int) $matches[1] > 50) {
                $kinds[(int) $propertyId] = 'low_accuracy';
            }
        }

        return $kinds;
    }

    protected function resolveResident(Property $property): ?Resident
    {
        $residents = $property->relationLoaded('residents')
            ? $property->residents
            : $property->residents()->get();

        /** @var Resident|null $primary */
        $primary = $residents->first(
            fn (Resident $resident) => $resident->is_primary_contact
                && $resident->status === ResidentStatus::ACTIVE
        );

        if ($primary !== null) {
            return $primary;
        }

        /** @var Resident|null $active */
        $active = $residents->first(
            fn (Resident $resident) => $resident->status === ResidentStatus::ACTIVE
        );

        return $active ?? $residents->first();
    }
}
