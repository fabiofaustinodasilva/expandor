<?php

namespace App\Domains\Company\Policies;

use App\Domains\Company\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view')
            || $this->hasAnyTeamPermission($user);
    }

    public function view(User $actor, User $model): bool
    {
        return $actor->company_id === $model->company_id
            && (
                $actor->hasPermission('users.view')
                || $this->hasAnyTeamPermission($actor)
            );
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create')
            || $user->hasPermission('users.manage');
    }

    public function update(User $actor, User $model): bool
    {
        return $actor->company_id === $model->company_id
            && (
                $actor->hasPermission('users.update')
                || $actor->hasPermission('users.manage')
            );
    }

    public function toggleStatus(User $actor, User $model): bool
    {
        return $actor->company_id === $model->company_id
            && $actor->id !== $model->id
            && (
                $actor->hasPermission('users.deactivate')
                || $actor->hasPermission('users.manage')
            );
    }

    public function resetPassword(User $actor, User $model): bool
    {
        return $actor->company_id === $model->company_id
            && (
                $actor->hasPermission('users.reset_password')
                || $actor->hasPermission('users.manage')
            );
    }

    public function managePermissions(User $actor, User $model): bool
    {
        return $actor->company_id === $model->company_id
            && (
                $actor->hasPermission('users.manage_permissions')
                || $actor->hasPermission('users.manage')
            );
    }

    protected function hasAnyTeamPermission(User $user): bool
    {
        return $user->hasPermission('users.manage')
            || $user->hasPermission('users.create')
            || $user->hasPermission('users.update')
            || $user->hasPermission('users.deactivate')
            || $user->hasPermission('users.reset_password')
            || $user->hasPermission('users.manage_permissions');
    }
}
