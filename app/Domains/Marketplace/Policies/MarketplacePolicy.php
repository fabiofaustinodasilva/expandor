<?php

namespace App\Domains\Marketplace\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Marketplace\Models\MarketplaceSetting;

class MarketplacePolicy
{
    public function manage(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasPermission('platform.access');
    }

    public function updateSettings(User $user, ?MarketplaceSetting $settings = null): bool
    {
        return $this->manage($user);
    }

    public function manageSections(User $user): bool
    {
        return $this->manage($user);
    }

    public function manageMedia(User $user): bool
    {
        return $this->manage($user);
    }

    public function preview(User $user): bool
    {
        return $this->manage($user);
    }
}
