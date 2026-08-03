<?php

namespace App\Domains\Security\Policies;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->company_id === $log->company_id
            && $user->hasPermission('audit.view');
    }
}
