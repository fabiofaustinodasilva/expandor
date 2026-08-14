<?php

namespace App\Domains\Maps\Repositories;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Maps\DTOs\MapFiltersDTO;
use App\Domains\Maps\Enums\MapCommercialGroup;
use App\Domains\Sales\Properties\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MapRepository
{
    public const SEARCH_LIMIT = 80;

    /**
     * Geolocated properties for map markers.
     *
     * @return Collection<int, Property>
     */
    public function findGeolocatedProperties(MapFiltersDTO $filters): Collection
    {
        $query = $this->baseQuery($filters)
            ->with([
                'address:id,street,number,neighborhood,reference,city_id,sector_id',
                'address.city:id,name',
                'residents' => fn ($q) => $q
                    ->select(['id', 'property_id', 'name', 'phone', 'whatsapp', 'document', 'birth_date', 'is_primary_contact', 'status'])
                    ->orderByDesc('is_primary_contact')
                    ->orderBy('name'),
            ])
            ->orderBy('id');

        if ($filters->hasTextSearch()) {
            $query->limit(self::SEARCH_LIMIT);
        }

        return $query->get();
    }

    /**
     * @return array<string, int>
     */
    public function countByStatus(MapFiltersDTO $filters): array
    {
        return $this->baseQuery($filters)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    /**
     * @return Builder<Property>
     */
    protected function baseQuery(MapFiltersDTO $filters): Builder
    {
        $statusValues = $this->resolveStatusFilter($filters);

        return Property::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when(
                $statusValues !== null,
                fn (Builder $query) => $query->whereIn('status', $statusValues)
            )
            ->when(
                $filters->city_id || $filters->sector_id,
                function (Builder $query) use ($filters): void {
                    $query->whereHas('address', function (Builder $addressQuery) use ($filters): void {
                        $addressQuery
                            ->when(
                                $filters->city_id,
                                fn (Builder $q) => $q->where('city_id', $filters->city_id)
                            )
                            ->when(
                                $filters->sector_id,
                                fn (Builder $q) => $q->where('sector_id', $filters->sector_id)
                            );
                    });
                }
            )
            ->when(
                $filters->date_from,
                fn (Builder $query) => $query->whereDate('updated_at', '>=', $filters->date_from)
            )
            ->when(
                $filters->date_to,
                fn (Builder $query) => $query->whereDate('updated_at', '<=', $filters->date_to)
            )
            ->when(
                $filters->hasBoundingBox() && ! $filters->hasTextSearch(),
                fn (Builder $query) => $query
                    ->whereBetween('latitude', [$filters->min_latitude, $filters->max_latitude])
                    ->whereBetween('longitude', [$filters->min_longitude, $filters->max_longitude])
            )
            ->when(
                $filters->search_owner_user_id !== null,
                function (Builder $query) use ($filters): void {
                    $ownerId = (int) $filters->search_owner_user_id;
                    $query->where(function (Builder $owned) use ($ownerId): void {
                        $owned->where('created_by', $ownerId)
                            ->orWhereHas(
                                'visits',
                                fn (Builder $visitQuery) => $visitQuery->where('user_id', $ownerId)
                            );
                    });
                }
            )
            ->when(
                $filters->search_owner_user_id === null && $filters->creator_ids !== null,
                fn (Builder $query) => $query->whereIn('created_by', $filters->creator_ids)
            )
            ->when(
                $filters->search_owner_user_id === null
                    && $filters->user_id
                    && $filters->creator_ids === null,
                fn (Builder $query) => $query->where('created_by', $filters->user_id)
            )
            ->when(
                $filters->campaign_id,
                function (Builder $query) use ($filters): void {
                    $this->applyCampaignTerritory($query, (int) $filters->campaign_id);
                }
            )
            ->when(
                $filters->hasTextSearch(),
                fn (Builder $query) => $this->applyTextSearch($query, (string) $filters->q)
            );
    }

    /**
     * Território da campanha: cidade obrigatória; setores do pivot se houver;
     * pivot vazio = cidade inteira (todos os setores). Sem setor fake.
     *
     * @param  Builder<Property>  $query
     */
    protected function applyCampaignTerritory(Builder $query, int $campaignId): void
    {
        $campaign = Campaign::query()
            ->select(['id', 'city_id', 'company_id'])
            ->with(['sectors:id'])
            ->find($campaignId);

        if ($campaign === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $sectorIds = $campaign->sectors->pluck('id')->map(fn ($id) => (int) $id)->all();

        $query->whereHas('address', function (Builder $addressQuery) use ($campaign, $sectorIds): void {
            $addressQuery->where('city_id', $campaign->city_id);

            if ($sectorIds !== []) {
                $addressQuery->where(function (Builder $sectorQuery) use ($sectorIds): void {
                    $sectorQuery->whereIn('sector_id', $sectorIds)
                        ->orWhereNull('sector_id');
                });
            }
        });
    }

    /**
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    protected function applyTextSearch(Builder $query, string $raw): Builder
    {
        $clean = str_replace(['%', '_', '\\'], '', $raw);
        if ($clean === '') {
            return $query->whereRaw('1 = 0');
        }

        $term = '%'.$clean.'%';
        $digits = preg_replace('/\D+/', '', $clean) ?? '';

        return $query->where(function (Builder $outer) use ($term, $digits): void {
            $outer->whereHas('residents', function (Builder $resident) use ($term, $digits): void {
                $resident->where(function (Builder $r) use ($term, $digits): void {
                    $r->where('name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('whatsapp', 'like', $term)
                        ->orWhere('document', 'like', $term);

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
                        ->orWhereHas(
                            'city',
                            fn (Builder $city) => $city->where('name', 'like', $term)
                        );
                });
            })->orWhereHas('visits', function (Builder $visit) use ($term): void {
                $visit->where(function (Builder $v) use ($term): void {
                    $v->where('plan', 'like', $term)
                        ->orWhereHas(
                            'product',
                            fn (Builder $product) => $product->where('name', 'like', $term)
                        )
                        ->orWhereHas(
                            'sale.items',
                            fn (Builder $item) => $item->where('product_name', 'like', $term)
                        );
                });
            });
        });
    }

    /**
     * @return list<string>|null
     */
    protected function resolveStatusFilter(MapFiltersDTO $filters): ?array
    {
        $uiStatuses = null;

        if ($filters->property_status) {
            $uiStatuses = [$filters->property_status];
        } elseif ($filters->commercial_groups !== null && $filters->commercial_groups !== []) {
            $values = [];
            foreach ($filters->commercial_groups as $group) {
                try {
                    $commercial = MapCommercialGroup::from($group);
                } catch (\ValueError) {
                    continue;
                }
                foreach ($commercial->statusValues() as $status) {
                    $values[] = $status;
                }
            }
            $uiStatuses = $values === [] ? null : array_values(array_unique($values));
        }

        $forced = $filters->forced_statuses;
        if ($forced === null || $forced === []) {
            return $uiStatuses;
        }

        if ($uiStatuses === null) {
            return $forced;
        }

        $intersect = array_values(array_intersect($forced, $uiStatuses));

        return $intersect === [] ? ['__none__'] : $intersect;
    }
}
