<?php

namespace App\Domains\CRM\Repositories;

use App\Domains\CRM\Enums\LeadStatus;
use App\Domains\CRM\Models\Lead;
use App\Domains\Company\Models\User;
use App\Domains\Campaigns\Models\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LeadRepository
{
    public function paginate(?string $status = null, ?int $assignedTo = null, int $perPage = 20): LengthAwarePaginator
    {
        return Lead::query()
            ->with(['assignee:id,name', 'campaign:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($assignedTo, fn ($q) => $q->where('assigned_to', $assignedTo))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function sellerOptions(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function campaignOptions(): Collection
    {
        return Campaign::query()->orderBy('name')->get(['id', 'name']);
    }

    public function countByStatus(): array
    {
        return Lead::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function countConverted(): int
    {
        return Lead::query()->where('status', LeadStatus::CONVERTED)->count();
    }
}
