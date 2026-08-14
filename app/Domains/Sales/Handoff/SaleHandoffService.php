<?php

namespace App\Domains\Sales\Handoff;

use App\Domains\Sales\Models\Sale;
use App\Domains\Security\Services\SecurityService;
use App\Domains\Visits\Models\Visit;
use Illuminate\Auth\Access\AuthorizationException;

class SaleHandoffService
{
    public function __construct(
        protected SaleHandoffFormatter $formatter,
        protected SecurityService $security,
    ) {}

    public function forSale(Sale $sale): SaleHandoffData
    {
        return $this->formatter->format($sale);
    }

    public function forVisit(Visit $visit): ?SaleHandoffData
    {
        $visit->loadMissing('sale');
        if ($visit->sale === null) {
            return null;
        }

        return $this->formatter->format($visit->sale);
    }

    public function recordOpened(Sale $sale, $user): void
    {
        $this->security->recordAudit(
            action: 'sale.office_handoff_opened',
            user: $user,
            auditable: $sale,
            newValues: ['sale_id' => $sale->id],
        );
    }

    public function recordCopied(Sale $sale, $user): void
    {
        $this->security->recordAudit(
            action: 'sale.office_handoff_copied',
            user: $user,
            auditable: $sale,
            newValues: ['sale_id' => $sale->id],
        );
    }

    public function assertSameCompany(Sale $sale, $user): void
    {
        if ($user === null || (int) $user->company_id !== (int) $sale->company_id) {
            throw new AuthorizationException;
        }
    }
}
