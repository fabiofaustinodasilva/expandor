<?php

namespace App\Domains\AI\Policies;

use App\Domains\AI\Models\AIConversation;
use App\Domains\Company\Models\User;

class AIConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ai.access');
    }

    public function view(User $user, AIConversation $conversation): bool
    {
        return $user->company_id === $conversation->company_id
            && $user->hasPermission('ai.access');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('ai.access');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermission('ai.manage');
    }

    public function delete(User $user, AIConversation $conversation): bool
    {
        return $user->company_id === $conversation->company_id
            && $user->hasPermission('ai.manage');
    }
}
