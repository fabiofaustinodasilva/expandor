<?php

namespace App\Domains\Marketplace\Growth\Repositories;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MarketplaceLeadRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MarketplaceLead
    {
        return MarketplaceLead::query()->create($data);
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return MarketplaceLead::query()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function countAll(): int
    {
        return MarketplaceLead::query()->count();
    }

    public function countByStatus(string $status): int
    {
        return MarketplaceLead::query()->where('status', $status)->count();
    }

    /**
     * @return Collection<int, MarketplaceLead>
     */
    public function recent(int $limit = 10): Collection
    {
        return MarketplaceLead::query()->orderByDesc('created_at')->limit($limit)->get();
    }
}
