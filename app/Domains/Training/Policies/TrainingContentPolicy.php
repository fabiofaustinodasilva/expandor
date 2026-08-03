<?php

namespace App\Domains\Training\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Training\Models\TrainingContent;

class TrainingContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('training.view')
            || $user->hasPermission('training.manage');
    }

    public function view(User $user, TrainingContent $content): bool
    {
        return $user->company_id === $content->company_id
            && ($user->hasPermission('training.view') || $user->hasPermission('training.manage'));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('training.manage');
    }

    public function update(User $user, TrainingContent $content): bool
    {
        return $user->company_id === $content->company_id
            && $user->hasPermission('training.manage');
    }

    public function complete(User $user, TrainingContent $content): bool
    {
        return $user->company_id === $content->company_id
            && $user->hasPermission('training.view');
    }
}
