<?php

namespace App\Domains\Communication\Policies;

use App\Domains\Communication\Models\Message;
use App\Domains\Company\Models\User;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('communication.view');
    }

    public function view(User $user, Message $message): bool
    {
        return $user->company_id === $message->company_id
            && $user->hasPermission('communication.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('communication.manage');
    }
}
