<?php

namespace App\Domains\Platform\Services;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;

/**
 * Registra eventos de ativação SaaS (idempotentes por ação+empresa quando aplicável).
 */
class ActivationEventRecorder
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function record(
        string $action,
        Company $company,
        ?User $actor = null,
        array $metadata = [],
        bool $once = false,
    ): ?AuditLog {
        if ($once && $this->has($company, $action)) {
            return null;
        }

        return $this->security->recordAudit(
            action: $action,
            user: $actor,
            auditable: $company,
            newValues: array_merge([
                'company_id' => $company->id,
                'user_id' => $actor?->id,
            ], $metadata),
            companyId: $company->id,
        );
    }

    public function has(Company $company, string $action): bool
    {
        return AuditLog::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('action', $action)
            ->exists();
    }

    public function started(Company $company, ?User $actor = null): void
    {
        $this->record('activation.started', $company, $actor, once: true);
    }

    public function firstLogin(Company $company, ?User $actor = null): void
    {
        $this->record('activation.first_login', $company, $actor, once: true);
    }

    public function firstCustomer(Company $company, ?User $actor = null, array $meta = []): void
    {
        $this->record('activation.first_customer', $company, $actor, $meta, once: true);
    }

    public function firstDeal(Company $company, ?User $actor = null, array $meta = []): void
    {
        $this->record('activation.first_deal', $company, $actor, $meta, once: true);
    }

    public function completed(Company $company, ?User $actor = null): void
    {
        $this->record('activation.completed', $company, $actor, once: true);
    }

    public function inactive(Company $company, ?User $actor = null, array $meta = []): void
    {
        $this->record('customer.inactive', $company, $actor, $meta);
    }

    public function reactivated(Company $company, ?User $actor = null, array $meta = []): void
    {
        $this->record('customer.reactivated', $company, $actor, $meta);
    }
}
