<?php

namespace App\Domains\Analytics\Repositories;

use App\Domains\Analytics\DTOs\AnalyticsFiltersDTO;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsRepository
{
    public const PENDING_FOLLOW_UPS_ALERT_THRESHOLD = 10;

    public const LOW_CONVERSION_MIN_VISITS = 10;

    public const LOW_CONVERSION_MAX_RATE = 5.0;

    public function countProperties(AnalyticsFiltersDTO $filters): int
    {
        return Property::query()
            ->when(
                $filters->user_id,
                fn (Builder $q) => $q->where('created_by', $filters->user_id)
            )
            ->when(
                $filters->city_id || $filters->sector_id,
                function (Builder $query) use ($filters): void {
                    $query->whereHas('address', function (Builder $address) use ($filters): void {
                        $address
                            ->when($filters->city_id, fn (Builder $q) => $q->where('city_id', $filters->city_id))
                            ->when($filters->sector_id, fn (Builder $q) => $q->where('sector_id', $filters->sector_id));
                    });
                }
            )
            ->when(
                $filters->date_from,
                fn (Builder $q) => $q->whereDate('created_at', '>=', $filters->date_from)
            )
            ->when(
                $filters->date_to,
                fn (Builder $q) => $q->whereDate('created_at', '<=', $filters->date_to)
            )
            ->count();
    }

    public function countVisits(AnalyticsFiltersDTO $filters): int
    {
        return $this->visitsQuery($filters)->count();
    }

    public function countVisitsByStatus(AnalyticsFiltersDTO $filters, VisitStatus $status): int
    {
        return $this->visitsQuery($filters)
            ->where('status', $status->value)
            ->count();
    }

    /**
     * @return array<string, int>
     */
    public function visitsGroupedByStatus(AnalyticsFiltersDTO $filters): array
    {
        return $this->visitsQuery($filters)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function countPendingFollowUps(AnalyticsFiltersDTO $filters): int
    {
        return FollowUp::query()
            ->where('status', FollowUpStatus::PENDING)
            ->when(
                $filters->user_id,
                fn (Builder $q) => $q->where('user_id', $filters->user_id)
            )
            ->when(
                $filters->date_from,
                fn (Builder $q) => $q->whereDate('scheduled_at', '>=', $filters->date_from)
            )
            ->when(
                $filters->date_to,
                fn (Builder $q) => $q->whereDate('scheduled_at', '<=', $filters->date_to)
            )
            ->when(
                $filters->city_id || $filters->sector_id,
                function (Builder $query) use ($filters): void {
                    $query->whereHas('visit.property.address', function (Builder $address) use ($filters): void {
                        $address
                            ->when($filters->city_id, fn (Builder $q) => $q->where('city_id', $filters->city_id))
                            ->when($filters->sector_id, fn (Builder $q) => $q->where('sector_id', $filters->sector_id));
                    });
                }
            )
            ->count();
    }

    /**
     * @return list<array{date: string, total: int}>
     */
    public function visitsByPeriod(AnalyticsFiltersDTO $filters, int $days = 14): array
    {
        $to = $filters->date_to
            ? Carbon::parse($filters->date_to)->endOfDay()
            : now()->endOfDay();

        $from = $filters->date_from
            ? Carbon::parse($filters->date_from)->startOfDay()
            : $to->copy()->subDays($days - 1)->startOfDay();

        $rows = $this->visitsQuery($filters)
            ->whereBetween('visited_at', [$from, $to])
            ->selectRaw('DATE(visited_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $series = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $series[] = [
                'date' => $key,
                'total' => (int) ($rows[$key] ?? 0),
            ];
            $cursor->addDay();
        }

        return $series;
    }

    /**
     * @return list<array{user_id: int, name: string, visits: int, interested: int, installations: int, conversion_rate: float}>
     */
    public function sellerProductivity(AnalyticsFiltersDTO $filters): array
    {
        $rows = $this->visitsQuery($filters)
            ->join('users', 'users.id', '=', 'visits.user_id')
            ->select([
                'visits.user_id',
                'users.name',
                DB::raw('COUNT(*) as visits'),
                DB::raw("SUM(CASE WHEN visits.status = '".VisitStatus::INTERESTED->value."' THEN 1 ELSE 0 END) as interested"),
                DB::raw("SUM(CASE WHEN visits.status = '".VisitStatus::INSTALLATION_REQUESTED->value."' THEN 1 ELSE 0 END) as installations"),
            ])
            ->groupBy('visits.user_id', 'users.name')
            ->limit(50)
            ->get();

        return $rows
            ->map(function ($row) {
                $visits = (int) $row->visits;
                $installations = (int) $row->installations;

                return [
                    'user_id' => (int) $row->user_id,
                    'name' => (string) $row->name,
                    'visits' => $visits,
                    'interested' => (int) $row->interested,
                    'installations' => $installations,
                    'conversion_rate' => $this->rate($installations, $visits),
                ];
            })
            ->sort(function (array $a, array $b): int {
                return [$b['installations'], $b['interested'], $b['conversion_rate'], $b['visits']]
                    <=> [$a['installations'], $a['interested'], $a['conversion_rate'], $a['visits']];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{sector_id: int|null, name: string, properties_worked: int, visits: int, interested: int, installations: int, conversion_rate: float}>
     */
    public function sectorPerformance(AnalyticsFiltersDTO $filters): array
    {
        $rows = $this->visitsQuery($filters)
            ->join('properties', 'properties.id', '=', 'visits.property_id')
            ->join('addresses', 'addresses.id', '=', 'properties.address_id')
            ->leftJoin('sectors', 'sectors.id', '=', 'addresses.sector_id')
            ->select([
                'addresses.sector_id',
                DB::raw("COALESCE(sectors.name, 'Sem setor') as name"),
                DB::raw('COUNT(DISTINCT visits.property_id) as properties_worked'),
                DB::raw('COUNT(*) as visits'),
                DB::raw("SUM(CASE WHEN visits.status = '".VisitStatus::INTERESTED->value."' THEN 1 ELSE 0 END) as interested"),
                DB::raw("SUM(CASE WHEN visits.status = '".VisitStatus::INSTALLATION_REQUESTED->value."' THEN 1 ELSE 0 END) as installations"),
            ])
            ->groupBy('addresses.sector_id', 'sectors.name')
            ->orderByDesc('visits')
            ->limit(20)
            ->get();

        return $rows->map(function ($row) {
            $visits = (int) $row->visits;
            $installations = (int) $row->installations;

            return [
                'sector_id' => $row->sector_id !== null ? (int) $row->sector_id : null,
                'name' => (string) $row->name,
                'properties_worked' => (int) $row->properties_worked,
                'visits' => $visits,
                'interested' => (int) $row->interested,
                'installations' => $installations,
                'conversion_rate' => $this->rate($installations, $visits),
            ];
        })->all();
    }

    /**
     * @return list<array{user_id: int, name: string, visits: int, interested: int, installations: int, conversion_rate: float}>
     */
    public function sellerRanking(AnalyticsFiltersDTO $filters, int $limit = 10): array
    {
        return Collection::make($this->sellerProductivity($filters))
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Vendedores ativos (role seller) sem visita hoje.
     *
     * @return list<array{user_id: int, name: string}>
     */
    public function sellersWithoutVisitsToday(?int $companyId = null): array
    {
        $today = now()->toDateString();

        return User::query()
            ->when($companyId, fn (Builder $q) => $q->where('company_id', $companyId))
            ->where('status', User::STATUS_ACTIVE)
            ->whereHas('role', fn (Builder $q) => $q->where('slug', Role::SELLER))
            ->whereDoesntHave('visits', fn (Builder $q) => $q->whereDate('visited_at', $today))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'user_id' => (int) $user->id,
                'name' => (string) $user->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function filterableSellers(): array
    {
        return User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->whereHas('role', fn (Builder $q) => $q->whereIn('slug', [
                Role::SELLER,
                Role::SUPERVISOR,
                Role::MANAGER,
            ]))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
            ])
            ->all();
    }

    public function rate(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 1);
    }

    /**
     * @return Builder<Visit>
     */
    protected function visitsQuery(AnalyticsFiltersDTO $filters): Builder
    {
        return Visit::query()
            ->when(
                $filters->user_id,
                fn (Builder $q) => $q->where('visits.user_id', $filters->user_id)
            )
            ->when(
                $filters->date_from,
                fn (Builder $q) => $q->whereDate('visits.visited_at', '>=', $filters->date_from)
            )
            ->when(
                $filters->date_to,
                fn (Builder $q) => $q->whereDate('visits.visited_at', '<=', $filters->date_to)
            )
            ->when(
                $filters->city_id || $filters->sector_id,
                function (Builder $query) use ($filters): void {
                    $query->whereHas('property.address', function (Builder $address) use ($filters): void {
                        $address
                            ->when($filters->city_id, fn (Builder $q) => $q->where('city_id', $filters->city_id))
                            ->when($filters->sector_id, fn (Builder $q) => $q->where('sector_id', $filters->sector_id));
                    });
                }
            );
    }
}
