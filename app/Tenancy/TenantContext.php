<?php

namespace App\Tenancy;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;

class TenantContext
{
    protected ?int $companyId = null;

    protected ?Company $company = null;

    protected ?User $user = null;

    protected bool $bypassed = false;

    public function set(?Company $company, ?User $user = null): void
    {
        $this->company = $company;
        $this->companyId = $company?->id;
        $this->user = $user;
        $this->bypassed = false;
    }

    public function setId(?int $companyId): void
    {
        $this->companyId = $companyId;
        $this->company = null;
        $this->bypassed = false;
    }

    public function id(): ?int
    {
        return $this->companyId;
    }

    public function company(): ?Company
    {
        if ($this->company === null && $this->companyId !== null) {
            $this->company = Company::query()->find($this->companyId);
        }

        return $this->company;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function check(): bool
    {
        return $this->companyId !== null;
    }

    public function bypass(bool $state = true): void
    {
        $this->bypassed = $state;
    }

    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    public function clear(): void
    {
        $this->companyId = null;
        $this->company = null;
        $this->user = null;
        $this->bypassed = false;
    }
}
