<?php

namespace App\Domains\Commissions\Services;

use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Commissions\Repositories\SalesCommissionRepository;
use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Validation\ValidationException;

class SalesCommissionService
{
    public function __construct(
        protected SalesCommissionRepository $repository,
        protected SecurityService $security,
    ) {}

    public function repository(): SalesCommissionRepository
    {
        return $this->repository;
    }

    public function approve(SalesCommission $commission, User $actor): SalesCommission
    {
        if ($commission->status !== SalesCommissionStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Somente comissões pendentes podem ser aprovadas.',
            ]);
        }

        $commission->update([
            'status' => SalesCommissionStatus::APPROVED,
            'approved_at' => now(),
            'approved_by' => $actor->id,
        ]);

        $this->security->recordAudit(
            action: 'sales_commission.approved',
            user: $actor,
            auditable: $commission,
            newValues: ['status' => SalesCommissionStatus::APPROVED->value],
        );

        return $commission->fresh();
    }

    public function markPaid(SalesCommission $commission, User $actor): SalesCommission
    {
        if (! in_array($commission->status, [SalesCommissionStatus::PENDING, SalesCommissionStatus::APPROVED], true)) {
            throw ValidationException::withMessages([
                'status' => 'Comissão já está paga ou em status inválido.',
            ]);
        }

        $updates = [
            'status' => SalesCommissionStatus::PAID,
            'paid_at' => now(),
            'paid_by' => $actor->id,
        ];

        if ($commission->status === SalesCommissionStatus::PENDING) {
            $updates['approved_at'] = $commission->approved_at ?? now();
            $updates['approved_by'] = $commission->approved_by ?? $actor->id;
        }

        $commission->update($updates);

        $this->security->recordAudit(
            action: 'sales_commission.paid',
            user: $actor,
            auditable: $commission,
            newValues: ['status' => SalesCommissionStatus::PAID->value],
        );

        return $commission->fresh();
    }
}
