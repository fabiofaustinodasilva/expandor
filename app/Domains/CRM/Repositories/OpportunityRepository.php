<?php

namespace App\Domains\CRM\Repositories;

use App\Domains\CRM\Enums\OpportunityStatus;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Models\PipelineStage;
use App\Domains\Company\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OpportunityRepository
{
    public function paginate(?string $status = null, ?int $ownerId = null, int $perPage = 20): LengthAwarePaginator
    {
        return Opportunity::query()
            ->with(['stage', 'owner:id,name', 'lead:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($ownerId, fn ($q) => $q->where('owner_id', $ownerId))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Collection<int, Opportunity>>
     */
    public function kanbanByStage(): Collection
    {
        $stages = PipelineStage::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $opportunities = Opportunity::query()
            ->with(['owner:id,name', 'lead:id,name'])
            ->where('status', OpportunityStatus::OPEN)
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('pipeline_stage_id');

        return $stages->mapWithKeys(function (PipelineStage $stage) use ($opportunities) {
            return [$stage->id => $opportunities->get($stage->id, collect())];
        });
    }

    public function sellerOptions(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function countByStatus(): array
    {
        return Opportunity::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function sumOpenAmount(): float
    {
        return (float) Opportunity::query()
            ->where('status', OpportunityStatus::OPEN)
            ->sum('amount');
    }

    public function sumWonAmount(?string $from = null, ?string $to = null): float
    {
        return (float) Opportunity::query()
            ->where('status', OpportunityStatus::WON)
            ->when($from, fn ($q) => $q->whereDate('closed_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('closed_at', '<=', $to))
            ->sum('amount');
    }

    /**
     * @return list<array{user_id: int, name: string, won_amount: float, won_count: int}>
     */
    public function wonBySeller(?string $from = null, ?string $to = null): array
    {
        return Opportunity::query()
            ->selectRaw('owner_id as user_id, COUNT(*) as won_count, COALESCE(SUM(amount), 0) as won_amount')
            ->where('status', OpportunityStatus::WON)
            ->whereNotNull('owner_id')
            ->when($from, fn ($q) => $q->whereDate('closed_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('closed_at', '<=', $to))
            ->groupBy('owner_id')
            ->orderByDesc('won_amount')
            ->get()
            ->map(function ($row) {
                $user = User::query()->find($row->user_id);

                return [
                    'user_id' => (int) $row->user_id,
                    'name' => $user?->name ?? 'Vendedor',
                    'won_amount' => (float) $row->won_amount,
                    'won_count' => (int) $row->won_count,
                ];
            })
            ->all();
    }

    public function countByStage(): array
    {
        return Opportunity::query()
            ->where('status', OpportunityStatus::OPEN)
            ->selectRaw('pipeline_stage_id, COUNT(*) as total')
            ->groupBy('pipeline_stage_id')
            ->pluck('total', 'pipeline_stage_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
