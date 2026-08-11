<?php

namespace App\Domains\Commissions\Repositories;

use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\User;
use App\Support\AppTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesCommissionRepository
{
    /**
     * @param  array{date_from?: ?string, date_to?: ?string, user_id?: ?int, campaign_id?: ?int, product_id?: ?int, status?: ?string}  $filters
     */
    public function query(array $filters = [], ?User $viewer = null): Builder
    {
        $query = SalesCommission::query()
            ->with([
                'user',
                'product',
                'saleItem',
                'visit.property.address',
                'visit.property.residents',
                'visit.sale.resident',
                'visit.campaign',
                'approver',
                'payer',
            ]);

        if ($viewer !== null && ! $viewer->hasPermission('commissions.manage')) {
            $query->where('user_id', $viewer->id);
        } elseif (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['date_from'])) {
            [$start] = AppTime::dayBoundsUtc($filters['date_from']);
            $query->where('earned_at', '>=', $start);
        }
        if (! empty($filters['date_to'])) {
            [, $end] = AppTime::dayBoundsUtc($filters['date_to']);
            $query->where('earned_at', '<=', $end);
        }
        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['campaign_id'])) {
            $query->whereHas('visit', fn (Builder $q) => $q->where('campaign_id', (int) $filters['campaign_id']));
        }

        return $query->orderByDesc('earned_at')->orderByDesc('id');
    }

    /**
     * @param  array{date_from?: ?string, date_to?: ?string, user_id?: ?int, campaign_id?: ?int, product_id?: ?int, status?: ?string}  $filters
     */
    public function paginate(array $filters = [], ?User $viewer = null, int $perPage = 30): LengthAwarePaginator
    {
        return $this->query($filters, $viewer)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array{date_from?: ?string, date_to?: ?string, user_id?: ?int, campaign_id?: ?int, product_id?: ?int, status?: ?string}  $filters
     * @return array{total_amount: float, sales_count: int, pending_amount: float, approved_amount: float, paid_amount: float}
     */
    public function summary(array $filters = [], ?User $viewer = null): array
    {
        $base = $this->query($filters, $viewer);

        $rows = (clone $base)
            ->reorder()
            ->toBase()
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(commission_amount),0) as total')
            ->groupBy('status')
            ->get();

        $byStatus = $rows->keyBy(fn ($r) => (string) $r->status);

        $totalAmount = (float) $rows->sum('total');
        $salesCount = (int) $rows->sum('cnt');

        return [
            'total_amount' => $totalAmount,
            'sales_count' => $salesCount,
            'pending_amount' => (float) ($byStatus[SalesCommissionStatus::PENDING->value]->total ?? 0),
            'approved_amount' => (float) ($byStatus[SalesCommissionStatus::APPROVED->value]->total ?? 0),
            'paid_amount' => (float) ($byStatus[SalesCommissionStatus::PAID->value]->total ?? 0),
        ];
    }

    /**
     * @return Collection<int, SalesCommission>
     */
    public function forVisit(int $visitId): Collection
    {
        return SalesCommission::query()->where('visit_id', $visitId)->get();
    }
}
