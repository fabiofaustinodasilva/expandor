<?php

namespace App\Domains\Customers\Services;

use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Validation\ValidationException;

/**
 * CRM delete of a "cliente" = SoftDeletes on Property (same aggregate as the map point).
 * Never forceDelete — FKs cascade on hard delete and would wipe visits/sales/commissions.
 */
class CustomerDeletionService
{
    public const BLOCKED_MESSAGE = 'Este cliente possui histórico de visitas ou vendas e não pode ser excluído. Você pode mantê-lo inativo/arquivado.';

    public function __construct(
        protected SecurityService $security,
    ) {}

    public function hasBlockingHistory(Property $property): bool
    {
        if ($property->relationLoaded('visits')) {
            return $property->visits->isNotEmpty();
        }

        return $property->visits()->exists();
    }

    public function deleteOrFail(Property $property, User $actor): void
    {
        if ($this->hasBlockingHistory($property)) {
            throw ValidationException::withMessages([
                'customer' => self::BLOCKED_MESSAGE,
            ]);
        }

        $snapshot = [
            'property_id' => $property->id,
            'status' => $property->status?->value ?? (string) $property->status,
            'address_id' => $property->address_id,
            'created_by' => $property->created_by,
        ];

        $property->forceFill([
            'deleted_by' => $actor->id,
            'deletion_reason' => 'customer.deleted',
        ])->save();

        $property->delete();

        $this->security->recordAudit(
            action: 'customer.deleted',
            user: $actor,
            auditable: $property,
            oldValues: $snapshot,
            newValues: [
                'deleted_at' => now()->toIso8601String(),
                'deleted_by' => $actor->id,
            ],
        );
    }
}
