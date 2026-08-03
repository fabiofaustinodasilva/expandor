<?php

namespace App\Domains\Campaigns\Policies;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('campaigns.view');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->company_id === $campaign->company_id
            && $user->hasPermission('campaigns.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('campaigns.manage');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->company_id === $campaign->company_id
            && $user->hasPermission('campaigns.manage');
    }

    public function activate(User $user, Campaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }

    public function pause(User $user, Campaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }

    public function finish(User $user, Campaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }
}
