<?php

namespace App\Domains\Integrations\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Integrations\Models\CompanyIntegration;

class CompanyIntegrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('integrations.view');
    }

    public function view(User $user, CompanyIntegration $integration): bool
    {
        return $user->company_id === $integration->company_id
            && $user->hasPermission('integrations.view');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermission('integrations.manage');
    }

    public function update(User $user, CompanyIntegration $integration): bool
    {
        return $user->company_id === $integration->company_id
            && $this->manage($user);
    }

    public function delete(User $user, CompanyIntegration $integration): bool
    {
        return $this->update($user, $integration);
    }
}
