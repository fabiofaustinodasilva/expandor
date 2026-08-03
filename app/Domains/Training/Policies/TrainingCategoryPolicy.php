<?php

namespace App\Domains\Training\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Training\Models\TrainingCategory;
use App\Domains\Training\Models\TrainingContent;

class TrainingCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('training.view')
            || $user->hasPermission('training.manage');
    }

    public function view(User $user, TrainingCategory $category): bool
    {
        return $user->company_id === $category->company_id
            && ($user->hasPermission('training.view') || $user->hasPermission('training.manage'));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('training.manage');
    }

    public function update(User $user, TrainingCategory $category): bool
    {
        return $user->company_id === $category->company_id
            && $user->hasPermission('training.manage');
    }
}
