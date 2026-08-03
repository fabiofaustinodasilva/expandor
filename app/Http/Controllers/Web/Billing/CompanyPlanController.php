<?php

namespace App\Http\Controllers\Web\Billing;

use App\Domains\Billing\Services\BillingService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyPlanController extends Controller
{
    public function __construct(
        protected BillingService $billing,
        protected TenantContext $tenant,
    ) {}

    public function show(Request $request): View
    {
        $company = $this->tenant->company() ?? $request->user()?->company();

        abort_if($company === null, 404);

        $this->authorize('billing.view', $company);

        return view('billing.plan', [
            'company' => $company,
            'overview' => $this->billing->overview($company),
        ]);
    }
}
