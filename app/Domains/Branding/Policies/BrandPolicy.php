<?php

namespace App\Domains\Branding\Policies;

use App\Domains\Branding\Models\Brand;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;

class BrandPolicy
{
    public function view(User $user, Brand|Company|null $brand = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return false;
        }

        if ($brand instanceof Brand) {
            return $user->company_id === $brand->company_id;
        }

        if ($brand instanceof Company) {
            return $user->company_id === $brand->id;
        }

        return $user->company_id !== null;
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Brand|Company|null $brand = null): bool
    {
        if (! $this->canManage($user)) {
            return false;
        }

        if ($brand instanceof Brand) {
            return $user->company_id === $brand->company_id;
        }

        if ($brand instanceof Company) {
            return $user->company_id === $brand->id;
        }

        return true;
    }

    protected function canManage(User $user): bool
    {
        if ($user->isPlatformAdmin()) {
            return false;
        }

        return $user->hasPermission('branding.manage');
    }
}
