<?php

namespace App\Domains\Security\Services;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditQueryService
{
    public function paginateForCompany(?Company $company = null, int $perPage = 30): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->with('user:id,name,email')
            ->latest('created_at')
            ->latest('id');

        if ($company !== null) {
            $query->withoutGlobalScopes()
                ->where('company_id', $company->id);
        }

        return $query->paginate($perPage);
    }

    public function auditableLabel(AuditLog $log): string
    {
        if ($log->auditable_type === null || $log->auditable_id === null) {
            return '—';
        }

        $type = class_basename((string) $log->auditable_type);

        return "{$type} #{$log->auditable_id}";
    }
}
