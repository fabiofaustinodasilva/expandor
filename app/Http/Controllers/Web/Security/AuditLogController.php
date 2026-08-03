<?php

namespace App\Http\Controllers\Web\Security;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Security\Services\AuditQueryService;
use App\Domains\Security\Services\SecurityService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditQueryService $audits,
        protected SecurityService $security,
        protected TenantContext $tenant,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $company = $this->tenant->company();

        return view('security.audit.index', [
            'logs' => $this->audits->paginateForCompany($company),
            'audits' => $this->audits,
            'health' => $this->security->productionHealth(),
        ]);
    }
}
