<?php

namespace App\Tenancy\Middleware;

use App\Domains\Company\Models\Company;
use App\Tenancy\Exceptions\TenantViolationException;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function __construct(
        protected TenantContext $context
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $company = $this->context->company();

        if ($company === null) {
            throw new TenantViolationException('Tenant context is not initialized.');
        }

        if ($company->status !== Company::STATUS_ACTIVE) {
            throw new TenantViolationException('Company is suspended and cannot operate.');
        }

        return $next($request);
    }
}
