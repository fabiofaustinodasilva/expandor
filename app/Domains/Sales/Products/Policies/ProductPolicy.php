<?php

namespace App\Domains\Sales\Products\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Sales\Products\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('commissions.manage')
            || $user->hasPermission('commissions.view_self')
            || $user->hasPermission('visits.contract')
            || $user->hasPermission('sales_app.access');
    }

    public function view(User $user, Product $product): bool
    {
        if ((int) $user->company_id !== (int) $product->company_id) {
            return false;
        }

        if ($user->hasPermission('commissions.manage')) {
            return true;
        }

        return $this->viewAny($user) && $product->isActive();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('commissions.manage');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission('commissions.manage')
            && (int) $user->company_id === (int) $product->company_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }

    public function manageStock(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
