<?php

namespace App\Domains\SalesApp\Policies;

use App\Domains\Company\Models\User;

class SalesAppPolicy
{
    public function access(User $user): bool
    {
        return $user->hasPermission('sales_app.access');
    }
}
