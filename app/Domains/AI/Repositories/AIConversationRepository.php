<?php

namespace App\Domains\AI\Repositories;

use App\Domains\AI\Models\AIConversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AIConversationRepository
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return AIConversation::query()
            ->with('user:id,name')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findForCompany(int $id): ?AIConversation
    {
        return AIConversation::query()
            ->with('user:id,name')
            ->find($id);
    }
}
