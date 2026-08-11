<?php

namespace App\Domains\Customers\Services;

use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Enums\ResidentStatus;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Support\FollowUpSchedule;
use App\Support\AppTime;
use App\Support\CommercialTerminology;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerQueryService
{
    public const PER_PAGE = 24;

    /**
     * @return LengthAwarePaginator<int, Property>
     */
    public function paginate(User $actor, ?string $q = null, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = $this->baseQuery($actor)
            ->with([
                'address.city:id,name',
                'creator:id,name',
                'residents' => fn ($r) => $r
                    ->select(['id', 'property_id', 'name', 'phone', 'whatsapp', 'document', 'email', 'is_primary_contact', 'status'])
                    ->orderByDesc('is_primary_contact')
                    ->orderBy('name'),
                'visits' => fn ($v) => $v
                    ->with(['user:id,name', 'campaign:id,name'])
                    ->orderByDesc('visited_at'),
            ])
            ->orderByDesc('updated_at');

        if ($q !== null && trim($q) !== '') {
            $this->applySearch($query, trim($q));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findForActor(User $actor, int $propertyId): ?Property
    {
        return $this->baseQuery($actor)
            ->whereKey($propertyId)
            ->with([
                'address.city:id,name',
                'creator:id,name',
                'residents' => fn ($r) => $r->orderByDesc('is_primary_contact')->orderBy('name'),
                'histories' => fn ($h) => $h->with('user:id,name')->orderBy('id'),
                'visits' => fn ($v) => $v
                    ->with([
                        'user:id,name',
                        'campaign:id,name',
                        'product:id,name',
                        'sale.items',
                        'followUps' => fn ($f) => $f->orderByDesc('scheduled_at'),
                    ])
                    ->orderByDesc('visited_at'),
            ])
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function presentCard(Property $property): array
    {
        $resident = $this->primaryResident($property);
        $lastVisit = $property->visits->first();
        $status = $property->status instanceof PropertyStatus
            ? $property->status
            : PropertyStatus::from((string) $property->status);

        $address = $property->address;

        return [
            'id' => $property->id,
            'name' => $resident?->name ?: ($address?->label() ?: 'Cliente #'.$property->id),
            'phone' => $resident?->phone,
            'whatsapp' => $resident?->whatsapp ?: $resident?->phone,
            'address' => $address?->label() ?: '—',
            'neighborhood' => $address?->neighborhood,
            'city' => $address?->city?->name,
            'last_visit_at' => $lastVisit?->visited_at?->timezone(\App\Support\AppTime::zone())->format('d/m/Y H:i'),
            'situation' => CommercialTerminology::customerSituation(
                $status,
                $lastVisit?->status instanceof VisitStatus ? $lastVisit->status : null
            ),
            'seller' => $property->creator?->name
                ?? $lastVisit?->user?->name
                ?? '—',
            'show_url' => route('customers.show', $property),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentDossier(Property $property): array
    {
        $resident = $this->primaryResident($property);
        $visits = $property->visits->sortByDesc(fn ($v) => $v->visited_at?->timestamp ?? 0)->values();
        $firstVisit = $visits->sortBy(fn ($v) => $v->visited_at?->timestamp ?? PHP_INT_MAX)->first();
        $lastVisit = $visits->first();
        $status = $property->status instanceof PropertyStatus
            ? $property->status
            : PropertyStatus::from((string) $property->status);

        $visitIds = $visits->pluck('id')->all();
        $commissions = $visitIds === []
            ? collect()
            : SalesCommission::query()
                ->whereIn('visit_id', $visitIds)
                ->orderByDesc('earned_at')
                ->orderByDesc('id')
                ->get();

        $pendingFollowUp = null;
        foreach ($visits as $visit) {
            foreach ($visit->followUps as $fu) {
                if ($fu->status === FollowUpStatus::PENDING) {
                    if ($pendingFollowUp === null
                        || ($fu->scheduled_at && $pendingFollowUp->scheduled_at && $fu->scheduled_at->lt($pendingFollowUp->scheduled_at))) {
                        $pendingFollowUp = $fu;
                    }
                }
            }
        }

        $products = [];
        foreach ($visits as $visit) {
            if ($visit->sale && $visit->sale->relationLoaded('items')) {
                foreach ($visit->sale->items as $item) {
                    $products[] = [
                        'product_name' => $item->product_name,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'line_total' => (float) $item->line_total,
                        'date' => $visit->visited_at?->timezone(\App\Support\AppTime::zone())->format('d/m/Y'),
                    ];
                }
            } elseif ($visit->plan || $visit->product) {
                $products[] = [
                    'product_name' => $visit->plan ?: $visit->product?->name,
                    'quantity' => 1,
                    'unit_price' => null,
                    'line_total' => null,
                    'date' => $visit->visited_at?->timezone(\App\Support\AppTime::zone())->format('d/m/Y'),
                ];
            }
        }

        $address = $property->address;
        $addressFull = implode(', ', array_filter([
            trim(($address?->street ?? '').' '.($address?->number ?? '')),
            $address?->complement,
            $address?->neighborhood,
            $address?->city?->name,
            $address?->zipcode,
        ]));

        $phoneDigits = preg_replace('/\D+/', '', (string) ($resident?->whatsapp ?: $resident?->phone)) ?? '';

        return [
            'id' => $property->id,
            'name' => $resident?->name ?: ($address?->label() ?: 'Cliente #'.$property->id),
            'phone' => $resident?->phone,
            'whatsapp' => $resident?->whatsapp ?: $resident?->phone,
            'document' => $resident?->document,
            'email' => $resident?->email,
            'address' => $addressFull !== '' ? $addressFull : '—',
            'latitude' => $property->latitude !== null ? (float) $property->latitude : null,
            'longitude' => $property->longitude !== null ? (float) $property->longitude : null,
            'situation' => CommercialTerminology::customerSituation(
                $status,
                $lastVisit?->status instanceof VisitStatus ? $lastVisit->status : null
            ),
            'status_label' => CommercialTerminology::propertyStatusLabel($status),
            'first_visit_at' => $firstVisit?->visited_at?->timezone(\App\Support\AppTime::zone())->format('d/m/Y H:i'),
            'last_visit_at' => $lastVisit?->visited_at?->timezone(\App\Support\AppTime::zone())->format('d/m/Y H:i'),
            'seller' => $property->creator?->name ?? $lastVisit?->user?->name ?? '—',
            'created_by' => $property->creator?->name,
            'updated_at' => $property->updated_at?->timezone(\App\Support\AppTime::zone())->format('d/m/Y H:i'),
            'campaign' => $lastVisit?->campaign?->name,
            'timeline' => $this->buildTimeline($property, $visits),
            'products' => $products,
            'commissions' => $commissions->map(fn (SalesCommission $c) => [
                'product_name' => $c->product_name,
                'amount' => (float) $c->commission_amount,
                'status' => $c->status?->label() ?? (string) $c->status,
                'earned_at' => $c->earned_at?->timezone(\App\Support\AppTime::zone())->format('d/m/Y'),
            ])->all(),
            'next_follow_up' => $pendingFollowUp ? [
                'at' => FollowUpSchedule::label($pendingFollowUp->scheduled_at),
                'has_time' => FollowUpSchedule::hasTime($pendingFollowUp->scheduled_at),
                'time_hint' => FollowUpSchedule::timeHint($pendingFollowUp->scheduled_at),
                'notes' => $pendingFollowUp->notes,
            ] : null,
            'visits' => $visits->map(fn ($visit) => [
                'id' => $visit->id,
                'at' => $visit->visited_at?->timezone(\App\Support\AppTime::zone())->format('d/m/Y H:i'),
                'result' => CommercialTerminology::visitResult($visit->status),
                'notes' => $visit->notes,
                'seller' => $visit->user?->name,
                'campaign' => $visit->campaign?->name,
            ])->all(),
            'actions' => [
                'whatsapp' => $phoneDigits !== ''
                    ? 'https://wa.me/55'.ltrim($phoneDigits, '55')
                    : null,
                'call' => $phoneDigits !== ''
                    ? 'tel:+'.(str_starts_with($phoneDigits, '55') ? $phoneDigits : '55'.$phoneDigits)
                    : null,
                'map' => route('map.index', ['property' => $property->id]),
                'new_visit' => route('map.index', ['property' => $property->id]),
                'new_sale' => route('map.index', ['property' => $property->id]),
                'schedule_return' => $lastVisit
                    ? route('visits.follow-ups.create', $lastVisit)
                    : route('map.index', ['property' => $property->id]),
            ],
        ];
    }

    /**
     * @param  Builder<Property>  $query
     */
    protected function baseQuery(User $actor): Builder
    {
        $query = Property::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($actor->role?->slug === Role::SELLER) {
            $uid = (int) $actor->id;
            $query->where(function (Builder $owned) use ($uid): void {
                $owned->where('created_by', $uid)
                    ->orWhereHas('visits', fn (Builder $v) => $v->where('user_id', $uid));
            });
        }

        return $query;
    }

    /**
     * @param  Builder<Property>  $query
     */
    protected function applySearch(Builder $query, string $raw): void
    {
        $clean = str_replace(['%', '_', '\\'], '', $raw);
        if ($clean === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $term = '%'.$clean.'%';
        $digits = preg_replace('/\D+/', '', $clean) ?? '';

        $query->where(function (Builder $outer) use ($term, $digits): void {
            $outer->whereHas('residents', function (Builder $resident) use ($term, $digits): void {
                $resident->where(function (Builder $r) use ($term, $digits): void {
                    $r->where('name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('whatsapp', 'like', $term)
                        ->orWhere('document', 'like', $term)
                        ->orWhere('email', 'like', $term);

                    if (strlen($digits) >= 3) {
                        $digitTerm = '%'.$digits.'%';
                        $r->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', '') LIKE ?",
                            [$digitTerm]
                        )->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(whatsapp,''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', '') LIKE ?",
                            [$digitTerm]
                        )->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(COALESCE(document,''), '.', ''), '-', ''), ' ', '') LIKE ?",
                            [$digitTerm]
                        );
                    }
                });
            })->orWhereHas('address', function (Builder $address) use ($term): void {
                $address->where(function (Builder $a) use ($term): void {
                    $a->where('street', 'like', $term)
                        ->orWhere('number', 'like', $term)
                        ->orWhere('neighborhood', 'like', $term)
                        ->orWhereHas('city', fn (Builder $c) => $c->where('name', 'like', $term));
                });
            })->orWhereHas('visits', function (Builder $visit) use ($term): void {
                $visit->where(function (Builder $v) use ($term): void {
                    $v->where('plan', 'like', $term)
                        ->orWhereHas('product', fn (Builder $p) => $p->where('name', 'like', $term))
                        ->orWhereHas('sale.items', fn (Builder $i) => $i->where('product_name', 'like', $term));
                });
            });
        });
    }

    protected function primaryResident(Property $property): ?Resident
    {
        $residents = $property->relationLoaded('residents')
            ? $property->residents
            : $property->residents()->get();

        /** @var Resident|null $primary */
        $primary = $residents->first(
            fn (Resident $r) => $r->is_primary_contact && $r->status === ResidentStatus::ACTIVE
        );

        if ($primary !== null) {
            return $primary;
        }

        return $residents->first(
            fn (Resident $r) => $r->status === ResidentStatus::ACTIVE
        ) ?? $residents->first();
    }

    /**
     * @param  Collection<int, \App\Domains\Visits\Models\Visit>  $visits
     * @return list<array{at: string, title: string, detail: ?string}>
     */
    protected function buildTimeline(Property $property, Collection $visits): array
    {
        $events = [];

        foreach ($property->histories as $history) {
            $events[] = [
                'sort' => $history->created_at?->timestamp ?? 0,
                'at' => $history->created_at?->timezone(\App\Support\AppTime::zone())->format('d/m/Y H:i') ?? '—',
                'title' => $history->description
                    ?: trim(
                        ($history->old_status ? CommercialTerminology::propertyStatusLabel($history->old_status) : '')
                        .' → '.
                        ($history->new_status ? CommercialTerminology::propertyStatusLabel($history->new_status) : '')
                    ),
                'detail' => $history->user?->name,
            ];
        }

        foreach ($visits as $visit) {
            $saleProducts = null;
            if ($visit->sale && $visit->sale->relationLoaded('items')) {
                $saleProducts = $visit->sale->items->pluck('product_name')->filter()->implode(', ');
            }

            $events[] = [
                'sort' => $visit->visited_at?->timestamp ?? 0,
                'at' => $visit->visited_at?->timezone(\App\Support\AppTime::zone())->format('d/m/Y H:i') ?? '—',
                'title' => CommercialTerminology::visitResult($visit->status),
                'detail' => trim(implode(' · ', array_filter([
                    $visit->user?->name,
                    $visit->campaign?->name,
                    $saleProducts,
                    $visit->notes,
                ]))),
            ];

            foreach ($visit->followUps as $fu) {
                $label = $fu->status === FollowUpStatus::PENDING
                    ? 'Retorno agendado'
                    : 'Retorno concluído';
                $events[] = [
                    'sort' => ($fu->completed_at ?? $fu->scheduled_at)?->timestamp ?? 0,
                    'at' => FollowUpSchedule::label($fu->completed_at ?? $fu->scheduled_at),
                    'title' => $label,
                    'detail' => $fu->notes,
                ];
            }
        }

        usort($events, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return array_map(static fn ($e) => [
            'at' => $e['at'],
            'title' => $e['title'],
            'detail' => $e['detail'] !== '' ? $e['detail'] : null,
        ], $events);
    }
}
