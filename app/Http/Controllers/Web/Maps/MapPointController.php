<?php

namespace App\Http\Controllers\Web\Maps;

use App\Domains\Company\Models\User;
use App\Domains\Company\Support\FieldOps\FieldOpsPolicyResolver;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Enums\PropertyType;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Models\PropertyHistory;
use App\Domains\Sales\Properties\Services\PropertyService;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Residents\Services\ResidentService;
use App\Domains\Security\Services\SecurityService;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Support\FollowUpSchedule;
use App\Http\Controllers\Controller;
use App\Support\AppTime;
use App\Support\CommercialTerminology;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MapPointController extends Controller
{
    public function __construct(
        protected PropertyService $properties,
        protected ResidentService $residents,
        protected SecurityService $security,
        protected FieldOpsPolicyResolver $fieldOps,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Property::class);

        $companyId = app(TenantContext::class)->id();

        $data = $request->validate([
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'sector_id' => [
                'nullable',
                'integer',
                Rule::exists('sectors', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:30'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::enum(PropertyStatus::class)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'gps_accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $property = DB::transaction(function () use ($data, $request) {
            $address = $this->properties->createAddress([
                'city_id' => $data['city_id'],
                'sector_id' => $data['sector_id'] ?? null,
                'street' => $data['street'],
                'number' => $data['number'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);

            $accuracyNote = '';
            if (isset($data['gps_accuracy'])) {
                $meters = (int) round((float) $data['gps_accuracy']);
                $accuracyNote = ' Precisão GPS: '.$meters.'m.';
                if ($meters > 50) {
                    $accuracyNote .= ' Baixa precisão.';
                } elseif ($meters > 10) {
                    $accuracyNote .= ' Boa precisão.';
                } else {
                    $accuracyNote .= ' Alta precisão.';
                }
            }

            $property = $this->properties->createProperty([
                'address_id' => $address->id,
                'type' => PropertyType::HOUSE->value,
                'status' => $data['status'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'notes' => $data['notes'] ?? null,
                'history_description' => 'Ponto adicionado no mapa.'.$accuracyNote,
            ], $request->user());

            $property->forceFill([
                'created_by' => $request->user()?->id,
            ])->save();

            if ((! empty($data['contact_name']) || ! empty($data['contact_phone'])) && $request->user()?->can('create', Resident::class)) {
                $this->residents->create($property, [
                    'name' => filled($data['contact_name'] ?? null) ? $data['contact_name'] : 'Contato',
                    'phone' => $data['contact_phone'] ?? null,
                    'is_primary_contact' => true,
                ]);
            }

            return $property->load(['address', 'residents']);
        });

        $resident = $property->residents->first();

        $this->security->recordAudit(
            action: 'point.created',
            user: $request->user(),
            auditable: $property,
            newValues: [
                'property_id' => $property->id,
                'status' => $property->status->value,
                'latitude' => (float) $property->latitude,
                'longitude' => (float) $property->longitude,
                'gps_accuracy' => $data['gps_accuracy'] ?? null,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Ponto salvo',
            'data' => [
                'property_id' => $property->id,
                'latitude' => (float) $property->latitude,
                'longitude' => (float) $property->longitude,
                'status' => $property->status->value,
                'status_label' => CommercialTerminology::propertyStatusLabel($property->status),
                'address' => trim(($property->address?->street ?? '').' '.($property->address?->number ?? '')),
                'resident_name' => $resident?->name,
                'resident_phone' => $resident?->phone,
            ],
        ], 201);
    }

    public function show(Property $property): JsonResponse
    {
        $this->authorize('view', $property);

        $property->load([
            'address.city',
            'residents',
            'creator',
            'histories' => fn ($q) => $q->with('user')->orderByDesc('id')->limit(12),
            'visits' => fn ($q) => $q->with(['user', 'campaign', 'sale.items', 'product'])
                ->orderByDesc('visited_at')
                ->limit(1),
        ]);

        $resident = $property->residents->sortByDesc('is_primary_contact')->first()
            ?? $property->residents->first();
        $lastVisit = $property->visits->first();
        $createdHistory = $property->histories->sortBy('id')->first();

        $nextFollowUp = FollowUp::query()
            ->where('status', FollowUpStatus::PENDING)
            ->whereHas('visit', fn ($q) => $q->where('property_id', $property->id))
            ->orderBy('scheduled_at')
            ->first();

        $user = auth()->user();
        $locationKind = $this->resolveLocationKind($property);

        $soldProduct = null;
        if ($lastVisit?->sale) {
            $names = $lastVisit->sale->items->pluck('product_name')->filter()->values();
            if ($names->isNotEmpty()) {
                $soldProduct = $names->implode(', ');
            } elseif ($lastVisit->plan) {
                $soldProduct = $lastVisit->plan;
            } elseif ($lastVisit->product?->name) {
                $soldProduct = $lastVisit->product->name;
            }
        } elseif ($lastVisit?->plan) {
            $soldProduct = $lastVisit->plan;
        } elseif ($lastVisit?->product?->name) {
            $soldProduct = $lastVisit->product->name;
        }

        $lastVisitResult = null;
        if ($lastVisit?->status) {
            $lastVisitResult = CommercialTerminology::visitStatusLabel($lastVisit->status);
        }

        $addressParts = array_filter([
            trim(($property->address?->street ?? '').' '.($property->address?->number ?? '')),
            $property->address?->neighborhood,
            $property->address?->city?->name,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'property_id' => $property->id,
                'latitude' => (float) $property->latitude,
                'longitude' => (float) $property->longitude,
                'status' => $property->status->value,
                'status_label' => CommercialTerminology::propertyStatusLabel($property->status),
                'address' => implode(', ', $addressParts),
                'city_id' => $property->address?->city_id,
                'street' => $property->address?->street,
                'number' => $property->address?->number,
                'resident_name' => $resident?->name,
                'resident_phone' => $resident?->phone,
                'resident_whatsapp' => $resident?->whatsapp,
                'resident_document' => $resident?->document,
                'resident_id' => $resident?->id,
                'notes' => $property->notes,
                'responsible' => $property->creator?->name
                    ?? $createdHistory?->user?->name
                    ?? $lastVisit?->user?->name,
                'campaign' => $lastVisit?->campaign?->name,
                'last_visit_at' => $lastVisit?->visited_at?->timezone(AppTime::zone())->format('d/m/Y H:i'),
                'last_visit_relative' => $lastVisit?->visited_at?->diffForHumans(),
                'last_visit_result' => $lastVisitResult,
                'sold_product' => $soldProduct,
                'next_follow_up_at' => $nextFollowUp
                    ? FollowUpSchedule::label($nextFollowUp->scheduled_at)
                    : null,
                'next_follow_up_relative' => $nextFollowUp?->scheduled_at?->diffForHumans(),
                'next_follow_up_notes' => $nextFollowUp?->notes,
                'next_follow_up_has_time' => $nextFollowUp
                    ? FollowUpSchedule::hasTime($nextFollowUp->scheduled_at)
                    : false,
                'next_follow_up_time_hint' => $nextFollowUp
                    ? FollowUpSchedule::timeHint($nextFollowUp->scheduled_at)
                    : null,
                'next_action' => $nextFollowUp
                    ? 'Retorno agendado'
                    : ($lastVisitResult ?: null),
                'created_by' => $property->creator?->name ?? $createdHistory?->user?->name,
                'created_at' => AppTime::formatInstant($property->created_at ?? $createdHistory?->created_at, 'd/m/Y'),
                'updated_at' => AppTime::formatInstant($property->updated_at),
                'can_edit' => $user?->can('update', $property) ?? false,
                'can_delete' => $user ? $this->canDeletePoint($user, $property) : false,
                'can_adjust' => $user ? $this->canAdjustPoint($user, $property) : false,
                'location_kind' => $locationKind,
                'location_label' => $this->locationKindLabel($locationKind),
                'history' => $property->histories->sortByDesc('id')->values()->map(fn ($h) => [
                    'at' => AppTime::formatInstant($h->created_at),
                    'user' => $h->user?->name,
                    'from' => $h->old_status
                        ? CommercialTerminology::propertyStatusLabel($h->old_status)
                        : null,
                    'to' => $h->new_status
                        ? CommercialTerminology::propertyStatusLabel($h->new_status)
                        : null,
                    'description' => $h->description,
                ])->all(),
            ],
        ]);
    }

    public function adjust(Request $request, Property $property): JsonResponse
    {
        abort_unless($this->canAdjustPoint($request->user(), $property), 403, 'Access denied.');

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $oldLat = (float) $property->latitude;
        $oldLng = (float) $property->longitude;
        $newLat = (float) $data['latitude'];
        $newLng = (float) $data['longitude'];

        DB::transaction(function () use ($property, $request, $oldLat, $oldLng, $newLat, $newLng): void {
            $property->update([
                'latitude' => $newLat,
                'longitude' => $newLng,
            ]);

            if ($property->address) {
                $property->address->update([
                    'latitude' => $newLat,
                    'longitude' => $newLng,
                ]);
            }

            PropertyHistory::query()->create([
                'property_id' => $property->id,
                'user_id' => $request->user()?->id,
                'old_status' => $property->status->value,
                'new_status' => $property->status->value,
                'description' => sprintf(
                    'Localização ajustada. De %.7f,%.7f para %.7f,%.7f.',
                    $oldLat,
                    $oldLng,
                    $newLat,
                    $newLng
                ),
                'latitude' => $newLat,
                'longitude' => $newLng,
                'created_at' => now(),
            ]);
        });

        $this->security->recordAudit(
            action: 'point.location_adjusted',
            user: $request->user(),
            auditable: $property,
            oldValues: ['latitude' => $oldLat, 'longitude' => $oldLng],
            newValues: ['latitude' => $newLat, 'longitude' => $newLng],
            request: $request,
        );

        $property->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Posição salva no mapa.',
            'data' => [
                'property_id' => $property->id,
                'latitude' => (float) $property->latitude,
                'longitude' => (float) $property->longitude,
                'location_kind' => 'adjusted',
                'location_label' => $this->locationKindLabel('adjusted'),
            ],
        ]);
    }

    public function update(Request $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $companyId = app(TenantContext::class)->id();

        $data = $request->validate([
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::enum(PropertyStatus::class)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $property = DB::transaction(function () use ($property, $data, $request) {
            $address = $property->address;
            if ($address) {
                $this->properties->updateAddress($address, [
                    'city_id' => $data['city_id'],
                    'sector_id' => $address->sector_id,
                    'street' => $data['street'],
                    'number' => $data['number'] ?? null,
                    'complement' => $address->complement,
                    'neighborhood' => $address->neighborhood,
                    'zipcode' => $address->zipcode,
                    'latitude' => $data['latitude'] ?? $property->latitude,
                    'longitude' => $data['longitude'] ?? $property->longitude,
                ]);
            }

            $newStatus = PropertyStatus::from($data['status']);
            if ($property->status !== $newStatus) {
                $this->properties->changeStatus($property, [
                    'status' => $newStatus->value,
                    'latitude' => $data['latitude'] ?? $property->latitude,
                    'longitude' => $data['longitude'] ?? $property->longitude,
                    'notes' => $data['notes'] ?? $property->notes,
                    'description' => 'Ponto atualizado pelo mapa operacional.',
                ], $request->user());
            } else {
                $property->update([
                    'latitude' => $data['latitude'] ?? $property->latitude,
                    'longitude' => $data['longitude'] ?? $property->longitude,
                    'notes' => $data['notes'] ?? $property->notes,
                ]);
            }

            $property->refresh()->load('residents');
            $resident = $property->residents->sortByDesc('is_primary_contact')->first()
                ?? $property->residents->first();

            if (! empty($data['contact_name'])) {
                if ($resident && $request->user()?->can('update', $resident)) {
                    $this->residents->update($resident, [
                        'name' => $data['contact_name'],
                        'phone' => $data['contact_phone'] ?? $resident->phone,
                        'is_primary_contact' => true,
                        'history_description' => 'Contato atualizado pelo mapa.',
                    ]);
                } elseif ($request->user()?->can('create', Resident::class)) {
                    $this->residents->create($property, [
                        'name' => $data['contact_name'],
                        'phone' => $data['contact_phone'] ?? null,
                        'is_primary_contact' => true,
                    ]);
                }
            }

            return $property->refresh()->load(['address', 'residents']);
        });

        $resident = $property->residents->first();

        $this->security->recordAudit(
            action: 'point.updated',
            user: $request->user(),
            auditable: $property,
            newValues: [
                'property_id' => $property->id,
                'status' => $property->status->value,
                'latitude' => (float) $property->latitude,
                'longitude' => (float) $property->longitude,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Cliente atualizado',
            'data' => [
                'property_id' => $property->id,
                'latitude' => (float) $property->latitude,
                'longitude' => (float) $property->longitude,
                'status' => $property->status->value,
                'status_label' => CommercialTerminology::propertyStatusLabel($property->status),
                'address' => trim(($property->address?->street ?? '').' '.($property->address?->number ?? '')),
                'resident_name' => $resident?->name,
                'resident_phone' => $resident?->phone,
            ],
        ]);
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        abort_unless($this->canDeletePoint($request->user(), $property), 403, 'Access denied.');

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $snapshot = [
            'property_id' => $property->id,
            'status' => $property->status->value,
            'latitude' => $property->latitude,
            'longitude' => $property->longitude,
            'address_id' => $property->address_id,
        ];

        $property->forceFill([
            'deleted_by' => $request->user()?->id,
            'deletion_reason' => $data['reason'] ?? null,
        ])->save();

        $property->delete();

        $this->security->recordAudit(
            action: 'point.deleted',
            user: $request->user(),
            auditable: $property,
            oldValues: $snapshot,
            newValues: [
                'deleted_at' => now()->toIso8601String(),
                'deleted_by' => $request->user()?->id,
                'reason' => $data['reason'] ?? null,
            ],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Ponto removido do mapa.',
        ]);
    }

    protected function canDeletePoint(?User $user, Property $property): bool
    {
        if ($user === null) {
            return false;
        }

        $policy = $this->fieldOps->resolveForUser($user);

        return $this->fieldOps->canDeleteProperty($user, $property, $policy);
    }

    protected function canAdjustPoint(?User $user, Property $property): bool
    {
        if ($user === null) {
            return false;
        }

        $policy = $this->fieldOps->resolveForUser($user);

        return $this->fieldOps->canAdjustProperty($user, $property, $policy);
    }

    protected function resolveLocationKind(Property $property): string
    {
        $histories = $property->relationLoaded('histories')
            ? $property->histories
            : $property->histories()->orderBy('id')->get();

        $text = $histories->pluck('description')->filter()->implode(' ');

        if (str_contains($text, 'Localização ajustada')) {
            return 'adjusted';
        }

        if (str_contains($text, 'Baixa precisão')) {
            return 'low_accuracy';
        }

        if (preg_match('/Precisão GPS:\s*(\d+)/u', $text, $matches) && (int) $matches[1] > 50) {
            return 'low_accuracy';
        }

        return 'gps';
    }

    protected function locationKindLabel(string $kind): string
    {
        return match ($kind) {
            'adjusted' => 'Ajustado manualmente',
            'low_accuracy' => 'Baixa precisão',
            default => 'GPS original',
        };
    }
}
