<?php

namespace App\Tenancy\Middleware;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Payments\Support\BillingSuspensionReasons;
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

        if ($company->status === Company::STATUS_ACTIVE) {
            return $next($request);
        }

        $isFinancial = BillingSuspensionReasons::isFinancial($company->suspension_reason)
            || (
                $company->suspension_reason === null
                && $company->status === Company::STATUS_SUSPENDED
                && $this->hasOpenBillingDebt((int) $company->id)
            );
        $routeName = (string) $request->route()?->getName();

        if ($isFinancial && $this->isBillingAllowlisted($routeName)) {
            return $next($request);
        }

        if ($isFinancial && $request->user()?->role?->slug === Role::ADMINISTRATOR) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento pendente. Regularize no Financeiro.',
                    'redirect' => route('company.finance.pending'),
                ], 403);
            }

            return redirect()->route('company.finance.pending');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Company is suspended and cannot operate.',
            ], 403);
        }

        abort(403, 'Company is suspended and cannot operate.');
    }

    protected function isBillingAllowlisted(string $routeName): bool
    {
        if ($routeName === '') {
            return false;
        }

        $prefixes = [
            'company.finance.',
            'company.subscription.',
            'logout',
            'profile.',
        ];

        foreach ($prefixes as $prefix) {
            if ($routeName === rtrim($prefix, '.') || str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function hasOpenBillingDebt(int $companyId): bool
    {
        return \App\Domains\Payments\Models\Invoice::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('status', [
                \App\Domains\Payments\Enums\InvoiceStatus::Open->value,
                \App\Domains\Payments\Enums\InvoiceStatus::Overdue->value,
            ])
            ->whereNull('paid_at')
            ->exists();
    }
}
