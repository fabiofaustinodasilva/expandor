<?php

namespace App\Domains\Training\Repositories;

use App\Domains\Company\Models\User;
use App\Domains\Training\Models\TrainingCategory;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Models\TrainingProgress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TrainingRepository
{
    public function paginateCategories(int $perPage = 15): LengthAwarePaginator
    {
        return TrainingCategory::query()
            ->withCount('contents')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginateContents(?int $categoryId = null, int $perPage = 15): LengthAwarePaginator
    {
        return TrainingContent::query()
            ->with('category:id,name')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, TrainingCategory>
     */
    public function activeCategoriesWithContents(): Collection
    {
        return TrainingCategory::query()
            ->where('active', true)
            ->with(['contents' => fn ($q) => $q->where('active', true)->orderBy('title')])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, TrainingCategory>
     */
    public function categoryOptions(): Collection
    {
        return TrainingCategory::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function findActiveContent(int $contentId): TrainingContent
    {
        return TrainingContent::query()
            ->where('active', true)
            ->whereHas('category', fn ($q) => $q->where('active', true))
            ->with('category:id,name')
            ->findOrFail($contentId);
    }

    public function progressForUser(User $user, int $contentId): ?TrainingProgress
    {
        return TrainingProgress::query()
            ->where('user_id', $user->id)
            ->where('training_content_id', $contentId)
            ->first();
    }

    /**
     * @return Collection<int, TrainingProgress>
     */
    public function progressMapForUser(User $user): Collection
    {
        return TrainingProgress::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('training_content_id');
    }
}
