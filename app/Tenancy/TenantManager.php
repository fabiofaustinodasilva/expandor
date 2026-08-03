<?php

namespace App\Tenancy;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Tenancy\Exceptions\TenantViolationException;

class TenantManager
{
    public function __construct(
        protected TenantContext $context
    ) {}

    public function initializeFromUser(User $user): void
    {
        $company = $user->company()->first();

        if ($company === null) {
            throw new TenantViolationException('User is not associated with a company.');
        }

        $this->context->set($company, $user);
    }

    public function initialize(Company $company, ?User $user = null): void
    {
        $this->context->set($company, $user);
    }

    public function id(): ?int
    {
        return $this->context->id();
    }

    public function company(): ?Company
    {
        return $this->context->company();
    }

    public function ensureActive(): void
    {
        $company = $this->context->company();

        if ($company === null) {
            throw new TenantViolationException('Tenant context is not initialized.');
        }

        if ($company->status !== Company::STATUS_ACTIVE) {
            throw new TenantViolationException('Company is not active.');
        }
    }

    public function clear(): void
    {
        $this->context->clear();
    }

    public function bypass(callable $callback): mixed
    {
        $this->context->bypass(true);

        try {
            return $callback();
        } finally {
            $this->context->bypass(false);
        }
    }
}
