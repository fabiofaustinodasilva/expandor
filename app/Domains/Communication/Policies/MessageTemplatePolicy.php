<?php

namespace App\Domains\Communication\Policies;

use App\Domains\Communication\Models\MessageTemplate;
use App\Domains\Company\Models\User;

class MessageTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('communication.view')
            || $user->hasPermission('communication.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('communication.manage');
    }

    public function update(User $user, MessageTemplate $template): bool
    {
        return $user->company_id === $template->company_id
            && $user->hasPermission('communication.manage');
    }
}
