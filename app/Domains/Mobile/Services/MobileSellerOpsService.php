<?php

namespace App\Domains\Mobile\Services;

use App\Domains\Auth\Services\MobileAuthService;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Commissions\Repositories\SalesCommissionRepository;
use App\Domains\Commissions\Support\CommissionAwardedPayload;
use App\Domains\Company\Models\User;
use App\Domains\Customers\Services\CustomerQueryService;
use App\Domains\Integrations\Services\MapFrontendConfigBuilder;
use App\Domains\Maps\DTOs\MapFiltersDTO;
use App\Domains\Maps\Services\MapQueryService;
use App\Domains\Mobile\Support\MobileApiTransformer;
use App\Domains\Platform\Services\FeatureFlagService;
use App\Domains\Sales\Handoff\OfficeSalesWhatsAppSettings;
use App\Domains\Sales\Handoff\SaleHandoffService;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Products\Services\ProductCatalogService;
use App\Domains\Sales\Support\SaleDueDays;
use App\Domains\Sales\SaleFields\SaleFieldKeys;
use App\Domains\Sales\SaleFields\SaleFieldsPolicyResolver;
use App\Domains\Company\Support\FieldOps\FieldOpsPolicyResolver;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Enums\PropertyType;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Models\PropertyHistory;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Residents\Enums\ResidentStatus;
use App\Domains\Sales\Residents\Services\ResidentService;
use App\Domains\Sales\Properties\Services\PropertyService;
use App\Domains\Sales\Territory\Repositories\TerritoryRepository;
use App\Domains\SalesApp\Services\SalesAppService;
use App\Domains\Security\Services\SecurityService;
use App\Domains\Visits\Actions\CompleteFollowUpAction;
use App\Domains\Visits\Actions\RegisterFirstApproachAction;
use App\Domains\Visits\Actions\RegisterVisitAction;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Services\VisitService;
use App\Domains\Visits\Support\FollowUpSchedule;
use App\Support\AppTime;
use App\Support\CommercialTerminology;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MobileSellerOpsService
{
    public function __construct(
        protected MobileAuthService $mobileAuth,
        protected MapFrontendConfigBuilder $mapConfig,
        protected MapQueryService $mapQuery,
        protected PropertyService $properties,
        protected ResidentService $residents,
        protected SecurityService $security,
        protected CustomerQueryService $customers,
        protected SalesAppService $salesApp,
        protected RegisterVisitAction $registerVisit,
        protected RegisterFirstApproachAction $firstApproach,
        protected CompleteFollowUpAction $completeFollowUp,
        protected VisitService $visits,
        protected ProductCatalogService $catalog,
        protected SaleFieldsPolicyResolver $saleFields,
        protected SalesCommissionRepository $commissions,
        protected TerritoryRepository $territory,
        protected FeatureFlagService $flags,
        protected FieldOpsPolicyResolver $fieldOps,
        protected SaleHandoffService $handoff,
        protected OfficeSalesWhatsAppSettings $officeWhatsApp,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function bootstrap(User $user): array
    {
        $profile = MobileApiTransformer::user($user);
        $company = $user->company;

        $flags = [];
        if ($company) {
            $flags = $this->flags->statesForCompany($company)->map(fn ($state) => [
                'key' => $state->key,
                'enabled' => $state->enabled,
            ])->values()->all();
        }

        $map = $company ? $this->mapConfig->forCompany($company)->toArray() : [
            'provider' => 'leaflet_osm',
            'fallback' => 'leaflet_osm',
            'reason' => 'no_company',
            'entitled' => false,
            'configured' => false,
        ];
        $googleVisual = ($map['provider'] ?? '') === 'google_maps';
        $map['google_visual'] = $googleVisual;
        $map['attribution'] = $googleVisual
            ? 'Map data © Google'
            : '© OpenStreetMap contributors';

            $activeCampaigns = $this->firstApproach->activeCampaignsFor($user)->loadMissing('city');
            $salePolicy = $this->saleFields->resolveForUser($user);

            $campaignPayload = $activeCampaigns->map(fn (Campaign $campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'city_id' => $campaign->city_id ? (int) $campaign->city_id : null,
                'city_name' => $campaign->city?->name,
            ])->values()->all();

        return [
            'user' => $profile,
            'company' => $profile['company'],
            'role' => $profile['role'],
            'permissions' => $profile['permissions'],
            'feature_flags' => $flags,
            'map' => $map,
            'timezone' => [
                'display' => AppTime::zone(),
                'today' => AppTime::today(),
            ],
            'campaign_context' => [
                'campaigns' => $campaignPayload,
                'has_campaign' => $activeCampaigns->isNotEmpty(),
                'requires_selection' => $activeCampaigns->count() > 1,
                'active_campaign_id' => $activeCampaigns->count() === 1
                    ? $activeCampaigns->first()->id
                    : null,
                'active_city_id' => $activeCampaigns->count() === 1
                    ? ($activeCampaigns->first()->city_id ? (int) $activeCampaigns->first()->city_id : null)
                    : null,
                'active_city_name' => $activeCampaigns->count() === 1
                    ? $activeCampaigns->first()->city?->name
                    : null,
                'no_campaign_message' => $activeCampaigns->isEmpty()
                    ? RegisterFirstApproachAction::NO_CAMPAIGN_MESSAGE
                    : null,
            ],
            'active_campaign_id' => $activeCampaigns->count() === 1
                ? $activeCampaigns->first()->id
                : null,
            'sale_fields' => [
                'required' => $salePolicy->checklist(),
                'labels' => SaleFieldKeys::labels(),
                'due_days' => SaleDueDays::ALLOWED,
            ],
            'office_handoff' => $this->officeHandoffCapability($company),
            'capabilities' => [
                'gps' => true,
                'offline' => false,
                'sync' => false,
                'push' => false,
                'presentation' => false,
            ],
            'session' => $this->mobileAuth->sessionPayload($user),
        ];
    }

    /**
     * Capability only — never expose the office WhatsApp number to the shell.
     *
     * @return array{enabled: bool, configured: bool, whatsapp_enabled: bool}
     */
    protected function officeHandoffCapability($company): array
    {
        if (! $company) {
            return [
                'enabled' => false,
                'configured' => false,
                'whatsapp_enabled' => false,
            ];
        }

        $office = $this->officeWhatsApp->forCompany($company);

        return [
            'enabled' => (bool) $office['enabled'],
            'configured' => ($office['digits'] ?? '') !== '',
            'can_send' => (bool) $office['enabled'],
            'whatsapp_configured' => ($office['digits'] ?? '') !== '',
            'whatsapp_enabled' => (bool) $office['enabled'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{markers: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    public function markers(User $user, array $filters): array
    {
        $dto = MapFiltersDTO::fromArray($filters);
        $dto = $this->mapQuery->constrainForUser(
            $dto,
            $user,
            isset($filters['campaign_id']) ? (int) $filters['campaign_id'] : null,
        );

        return [
            'markers' => array_map(
                static fn ($marker) => $marker->toArray(),
                $this->mapQuery->markers($dto),
            ),
            'summary' => $this->mapQuery->summary($dto),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createPoint(User $user, array $data): array
    {
        $property = DB::transaction(function () use ($data, $user) {
            $address = $this->properties->createAddress([
                'city_id' => $data['city_id'],
                'sector_id' => $data['sector_id'] ?? null,
                'street' => $data['street'],
                'number' => $data['number'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);

            $status = $data['status'] ?? PropertyStatus::NEW->value;

            $property = $this->properties->createProperty([
                'address_id' => $address->id,
                'type' => PropertyType::HOUSE->value,
                'status' => $status instanceof PropertyStatus ? $status->value : (string) $status,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'notes' => $data['notes'] ?? null,
                'history_description' => 'Ponto adicionado no app.',
            ], $user);

            $property->forceFill(['created_by' => $user->id])->save();

            if ((! empty($data['contact_name']) || ! empty($data['contact_phone'])) && $user->can('create', Resident::class)) {
                $this->residents->create($property, [
                    'name' => filled($data['contact_name'] ?? null) ? $data['contact_name'] : 'Contato',
                    'phone' => $data['contact_phone'] ?? null,
                    'is_primary_contact' => true,
                ]);
            }

            return $property->load(['address', 'residents']);
        });

        $this->security->recordAudit(
            action: 'point.created',
            user: $user,
            auditable: $property,
            newValues: [
                'property_id' => $property->id,
                'status' => $property->status->value,
                'latitude' => (float) $property->latitude,
                'longitude' => (float) $property->longitude,
                'source' => 'mobile',
            ],
        );

        return $this->pointMarker($property);
    }

    /**
     * Primeira abordagem — mesmo domínio do web (RegisterFirstApproachAction).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function registerFirstApproach(User $user, array $data): array
    {
        $resolved = $this->resolveSectorFromText(
            isset($data['city_id']) ? (int) $data['city_id'] : null,
            isset($data['sector_name']) ? trim((string) $data['sector_name']) : null,
        );

        if ($resolved['sector_id'] !== null) {
            $data['sector_id'] = $resolved['sector_id'];
        }
        if ($resolved['neighborhood'] !== null) {
            $data['neighborhood'] = $resolved['neighborhood'];
        }
        unset($data['sector_name']);

        if (! filled($data['street'] ?? null)) {
            $data['street'] = 'Posição no mapa';
        }

        unset($data['complete_sale']);

        $result = $this->firstApproach->execute($data, $user);
        $property = $result['property']->load(['address', 'residents']);
        $visit = $result['visit']->load(['sale.items', 'followUps', 'campaign']);

        $payload = array_merge($this->pointMarker($property), [
            'visit_id' => $visit->id,
            'visit_status' => $visit->status->value,
            'campaign_id' => $visit->campaign_id,
            'first_approach' => true,
        ]);

        if ($visit->status === VisitStatus::INSTALLATION_REQUESTED) {
            $payload = $this->attachSaleOutcome($payload, $visit);
        }

        return $payload;
    }

    /**
     * UX mobile: setor/bairro digitado. Se casar com Sector do território → sector_id;
     * senão persiste como neighborhood (Address), sem inventar sector_id.
     *
     * @return array{sector_id: ?int, neighborhood: ?string}
     */
    public function resolveSectorFromText(?int $cityId, ?string $sectorName): array
    {
        $name = trim((string) $sectorName);
        if ($name === '' || $cityId === null) {
            return ['sector_id' => null, 'neighborhood' => $name !== '' ? $name : null];
        }

        $sectors = $this->territory->activeSectors($cityId);
        $match = $sectors->first(function ($sector) use ($name) {
            return mb_strtolower(trim((string) $sector->name)) === mb_strtolower($name);
        });

        if ($match) {
            return ['sector_id' => (int) $match->id, 'neighborhood' => null];
        }

        return ['sector_id' => null, 'neighborhood' => $name];
    }

    public function findPoint(User $user, int $id): ?Property
    {
        return $this->customers->findForActor($user, $id);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentPointCard(Property $property): array
    {
        return array_merge($this->customers->presentCard($property), $this->pointMarker($property));
    }

    public function presentPoint(Property $property, ?User $actor = null): array
    {
        $property->loadMissing(['address.city', 'residents', 'visits']);
        $dossier = $this->customers->presentDossier($property);
        $resident = $property->residents->sortByDesc('is_primary_contact')->first()
            ?? $property->residents->first();
        $lastVisit = $property->visits->first();
        $canAdjust = false;
        if ($actor) {
            $policy = $this->fieldOps->resolveForUser($actor);
            $canAdjust = $this->fieldOps->canAdjustProperty($actor, $property, $policy);
        }

        return array_merge($dossier, [
            'id' => $property->id,
            'property_id' => $property->id,
            'latitude' => (float) $property->latitude,
            'longitude' => (float) $property->longitude,
            'status' => $this->propertyStatus($property)->value,
            'status_label' => CommercialTerminology::propertyStatusLabel($this->propertyStatus($property)),
            'resident_name' => $resident?->name,
            'resident_phone' => $resident?->phone,
            'resident_whatsapp' => $resident?->whatsapp ?: $resident?->phone,
            'last_visit_at' => $lastVisit?->visited_at
                ? AppTime::formatInstant($lastVisit->visited_at)
                : null,
            'tel' => $resident?->phone ? 'tel:'.$resident->phone : null,
            'wa' => ($resident?->whatsapp ?: $resident?->phone)
                ? 'https://wa.me/'.preg_replace('/\D+/', '', (string) ($resident->whatsapp ?: $resident->phone))
                : null,
            'can_adjust' => $canAdjust,
            'last_sale_id' => $this->lastSaleIdForProperty($property),
            'city_name' => $property->address?->city?->name,
            'city_id' => $property->address?->city_id,
            'street' => $property->address?->street,
            'number' => $property->address?->number,
            'neighborhood' => $property->address?->neighborhood,
            'reference' => $property->address?->reference,
            'resident_document' => $resident?->document,
            'resident_birth_date' => $resident?->birth_date?->format('d/m/Y'),
        ]);
    }

    /**
     * Reposiciona lat/lng do imóvel (mesmo domínio do web MapPointController::adjust).
     *
     * @return array<string, mixed>
     */
    public function adjustPointLocation(User $user, Property $property, float $latitude, float $longitude): array
    {
        $policy = $this->fieldOps->resolveForUser($user);
        if (! $this->fieldOps->canAdjustProperty($user, $property, $policy)) {
            throw new AuthorizationException('Access denied.');
        }

        $oldLat = (float) $property->latitude;
        $oldLng = (float) $property->longitude;

        DB::transaction(function () use ($property, $user, $oldLat, $oldLng, $latitude, $longitude): void {
            $property->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            if ($property->address) {
                $property->address->update([
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);
            }

            PropertyHistory::query()->create([
                'property_id' => $property->id,
                'user_id' => $user->id,
                'old_status' => $property->status->value,
                'new_status' => $property->status->value,
                'description' => sprintf(
                    'Localização ajustada. De %.7f,%.7f para %.7f,%.7f.',
                    $oldLat,
                    $oldLng,
                    $latitude,
                    $longitude
                ),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'created_at' => now(),
            ]);
        });

        $this->security->recordAudit(
            action: 'point.location_adjusted',
            user: $user,
            auditable: $property,
            oldValues: ['latitude' => $oldLat, 'longitude' => $oldLng],
            newValues: ['latitude' => $latitude, 'longitude' => $longitude, 'source' => 'mobile'],
        );

        $property->refresh()->load(['address', 'residents']);

        return array_merge($this->pointMarker($property), [
            'location_kind' => 'adjusted',
            'can_adjust' => true,
        ]);
    }

    public function paginatePoints(User $user, ?string $q, ?string $status, int $perPage = 24): LengthAwarePaginator
    {
        $page = $this->customers->paginate($user, $q, $perPage);
        if (! filled($status)) {
            return $page;
        }

        $page->setCollection(
            $page->getCollection()->filter(function (Property $property) use ($status) {
                $value = $property->status instanceof PropertyStatus
                    ? $property->status->value
                    : (string) $property->status;

                return $value === $status;
            })->values()
        );

        return $page;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function registerPointVisit(User $user, Property $property, array $data): array
    {
        unset($data['complete_sale']);
        $campaign = $this->firstApproach->resolveCampaign($user, $data['campaign_id'] ?? null);
        $data['campaign_id'] = $campaign->id;
        $data['property_id'] = $property->id;
        $data['user_id'] = $user->id;

        $visit = $this->registerVisit->execute($campaign, $data, $user);

        if (($data['status'] ?? null) === VisitStatus::RETURN_LATER->value && ! empty($data['follow_up_at'])) {
            $this->visits->scheduleFollowUp($visit, [
                'scheduled_at' => $data['follow_up_at'],
                'notes' => $data['notes'] ?? null,
            ], $user);
        }

        $visit->load(['sale.items', 'followUps', 'property']);
        $payload = [
            'visit' => MobileApiTransformer::visit($visit),
            'status' => $visit->status->value,
            'property_id' => $property->id,
        ];

        if ($visit->status === VisitStatus::INSTALLATION_REQUESTED) {
            $payload = $this->attachSaleOutcome($payload, $visit);
            $sale = $visit->sale;
            $payload['total'] = $sale?->negotiated_amount;
            $payload['items'] = $sale?->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])->values()->all();
        }

        return $payload;
    }

    public function paginateAgenda(User $user, string $scope = 'today', int $perPage = 20): LengthAwarePaginator
    {
        $query = FollowUp::query()
            ->where('user_id', $user->id)
            ->operationalPending()
            ->with([
                'visit.property.address:id,street,number,neighborhood',
                'visit.property.residents:id,property_id,name,phone,whatsapp,is_primary_contact,status',
                'visit.campaign:id,name',
                'visit.user:id,name',
                'user:id,name',
            ])
            ->orderBy('scheduled_at');

        $today = AppTime::today();
        if ($scope === 'today') {
            $query->whereDate('scheduled_at', $today);
        } elseif ($scope === 'overdue') {
            $query->whereDate('scheduled_at', '<', $today);
        } elseif ($scope === 'upcoming') {
            $query->whereDate('scheduled_at', '>', $today);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentFollowUp(FollowUp $followUp): array
    {
        $visit = $followUp->visit;
        $property = $visit?->property;
        $resident = $this->agendaContact($property);
        $seller = $visit?->user ?? $followUp->user;
        $contactName = trim((string) ($resident?->name ?? ''));
        $contactPhone = trim((string) ($resident?->phone ?: $resident?->whatsapp ?: ''));
        if ($contactName === '') {
            $contactName = $contactPhone !== '' ? $contactPhone : 'Cliente sem nome';
        }

        $address = $property?->address;
        $addressLabel = trim((string) ($address?->label() ?: ''));
        if ($addressLabel === '') {
            $addressLabel = trim(($address?->street ?? '').' '.($address?->number ?? ''));
        }

        $sellerName = trim((string) ($seller?->name ?? ''));

        return [
            'id' => $followUp->id,
            'visit_id' => $followUp->visit_id,
            'scheduled_at' => $followUp->scheduled_at?->toIso8601String(),
            'scheduled_label' => FollowUpSchedule::label($followUp->scheduled_at),
            'status' => $followUp->status?->value ?? $followUp->status,
            'notes' => $followUp->notes,
            'property_id' => $property?->id,
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone !== '' ? $contactPhone : null,
            'seller_id' => $seller?->id,
            'seller_name' => $sellerName !== '' ? $sellerName : null,
            'address' => $addressLabel,
            'campaign' => $visit?->campaign?->name,
        ];
    }

    protected function agendaContact(?Property $property): ?Resident
    {
        if ($property === null) {
            return null;
        }

        $residents = $property->relationLoaded('residents')
            ? $property->residents
            : $property->residents()->get();

        $primary = $residents->first(
            fn (Resident $resident) => $resident->is_primary_contact
                && $resident->status === ResidentStatus::ACTIVE
        );
        if ($primary !== null) {
            return $primary;
        }

        return $residents->first(
            fn (Resident $resident) => $resident->status === ResidentStatus::ACTIVE
        ) ?? $residents->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function completeSellerFollowUp(User $user, FollowUp $followUp, array $data): array
    {
        if ((int) $followUp->user_id !== (int) $user->id) {
            throw new AuthorizationException('Somente o responsável pode concluir este retorno.');
        }

        unset($data['complete_sale']);
        $result = $this->completeFollowUp->execute($followUp, $data, $user);
        $visit = $result['visit'];
        $payload = [
            'follow_up' => $this->presentFollowUp($result['follow_up']->fresh()),
            'visit' => MobileApiTransformer::visit($visit),
            'next_follow_up' => $result['next_follow_up']
                ? $this->presentFollowUp($result['next_follow_up'])
                : null,
        ];

        if ($visit->status === VisitStatus::INSTALLATION_REQUESTED) {
            $payload = $this->attachSaleOutcome($payload, $visit->fresh(['sale.items']));
        }

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function products(?string $q = null): array
    {
        return $this->catalog->activeCatalogForSeller($q)
            ->filter(fn ($product) => $product->isSellable())
            ->map(function ($product) {
                $video = $product->embeddableVideoUrl();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->categoryLabel(),
                    'description' => (string) ($product->description ?? ''),
                    'benefits' => $product->benefitList(),
                    'price' => $product->price,
                    'stock_control' => $product->stock_control,
                    'stock_quantity' => $product->stock_quantity,
                    'commission_type' => $product->commissionType()->value,
                    'commission_amount' => $product->commission_amount,
                    'commission_percentage' => $product->commission_percentage,
                    'image' => $product->imagePresentPublicUrl(),
                    'image_original' => $product->imageOriginalPublicUrl(),
                    'image_thumb' => $product->imageThumbPublicUrl(),
                    'video' => $video,
                    'video_embed' => $video !== null
                        && (str_contains($video, 'youtube.com/embed')
                            || str_contains($video, 'player.vimeo.com')),
                    'available' => true,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array<string, mixed>>, summary: array<string, mixed>, meta: array<string, int>}
     */
    public function commissions(User $user, array $filters, int $perPage = 30): array
    {
        $filters['user_id'] = $user->id;
        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_from'] = AppTime::now()->subDays(30)->toDateString();
            $filters['date_to'] = AppTime::today();
        }

        $page = $this->commissions->paginate($filters, $user, $perPage);
        $items = $page->getCollection()->map(fn ($row) => [
            'id' => $row->id,
            'product' => $row->product?->name,
            'sale_value' => $row->saleItem?->total_amount ?? $row->visit?->sale?->negotiated_amount,
            'commission_amount' => $row->commission_amount,
            'status' => $row->status?->value ?? $row->status,
            'earned_at' => AppTime::formatInstant($row->earned_at),
            'earned_at_iso' => $row->earned_at?->toIso8601String(),
        ])->values()->all();

        return [
            'items' => $items,
            'summary' => $this->commissions->summary($filters, $user),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function results(User $user): array
    {
        $dashboard = $this->salesApp->dashboard($user);
        $commissions = $this->commissions->summary([
            'user_id' => $user->id,
            'date_from' => AppTime::now()->subDays(30)->toDateString(),
            'date_to' => AppTime::today(),
        ], $user);

        return array_merge($dashboard, [
            'commissions' => $commissions,
        ]);
    }

    /**
     * @return array{cities: list<array<string, mixed>>, sectors: list<array<string, mixed>>}
     */
    public function territory(?int $cityId = null): array
    {
        $cities = $this->territory->activeCities()->map(fn ($city) => [
            'id' => $city->id,
            'name' => $city->name,
            'state' => $city->state ?? null,
        ])->values()->all();

        $sectors = $this->territory->activeSectors($cityId)->map(fn ($sector) => [
            'id' => $sector->id,
            'name' => $sector->name,
            'city_id' => $sector->city_id,
        ])->values()->all();

        return ['cities' => $cities, 'sectors' => $sectors];
    }

    /**
     * @return array<string, mixed>
     */
    public function pointMarker(Property $property): array
    {
        $resident = $property->residents->first();

        return [
            'id' => $property->id,
            'property_id' => $property->id,
            'latitude' => (float) $property->latitude,
            'longitude' => (float) $property->longitude,
            'status' => $this->propertyStatus($property)->value,
            'status_label' => CommercialTerminology::propertyStatusLabel($this->propertyStatus($property)),
            'address' => trim(($property->address?->street ?? '').' '.($property->address?->number ?? '')),
            'resident_name' => $resident?->name,
            'resident_phone' => $resident?->phone,
            'created_at' => AppTime::formatInstant($property->created_at),
        ];
    }

    private function propertyStatus(Property $property): PropertyStatus
    {
        return $property->status instanceof PropertyStatus
            ? $property->status
            : PropertyStatus::from((string) $property->status);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function attachSaleOutcome(array $payload, Visit $visit): array
    {
        $awarded = CommissionAwardedPayload::fromVisit($visit);
        if ($awarded !== null) {
            $payload['commission_awarded'] = $awarded;
            $payload['sale_id'] = $awarded['sale_id'];
            $payload['commission_id'] = $awarded['commission_id'];
            $payload['commission_amount'] = $awarded['amount'];
        }

        $office = $this->handoff->forVisit($visit);
        if ($office !== null) {
            $payload['office_handoff'] = $office->toArray();
            $payload['sale_id'] = $payload['sale_id'] ?? $office->saleId;
        }

        return $payload;
    }

    protected function lastSaleIdForProperty(Property $property): ?int
    {
        $id = Sale::query()
            ->where('company_id', $property->company_id)
            ->whereHas('visit', fn ($q) => $q->where('property_id', $property->id))
            ->latest('id')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * @return array<string, int>
     */
    public function pageMeta(LengthAwarePaginator $page): array
    {
        return [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ];
    }
}
