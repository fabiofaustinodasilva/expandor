<?php

namespace App\Domains\Training\Services;

use App\Domains\Company\Models\User;
use App\Domains\Training\Enums\TrainingContentType;
use App\Domains\Training\Models\TrainingCategory;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Models\TrainingProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): TrainingCategory
    {
        return TrainingCategory::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(TrainingCategory $category, array $data): TrainingCategory
    {
        $category->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => array_key_exists('active', $data)
                ? (bool) $data['active']
                : $category->active,
        ]);

        return $category->refresh();
    }

    public function toggleCategory(TrainingCategory $category): TrainingCategory
    {
        $category->update(['active' => ! $category->active]);

        return $category->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createContent(array $data): TrainingContent
    {
        $this->assertCategoryExists((int) $data['category_id']);
        $type = TrainingContentType::from($data['type']);
        $this->assertContentPayload($type, $data);

        return TrainingContent::query()->create([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $type,
            'content' => $data['content'] ?? null,
            'url' => $data['url'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateContent(TrainingContent $content, array $data): TrainingContent
    {
        $this->assertCategoryExists((int) $data['category_id']);
        $type = TrainingContentType::from($data['type']);
        $this->assertContentPayload($type, $data);

        $content->update([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $type,
            'content' => $data['content'] ?? null,
            'url' => $data['url'] ?? null,
            'active' => array_key_exists('active', $data)
                ? (bool) $data['active']
                : $content->active,
        ]);

        return $content->refresh();
    }

    public function startProgress(User $user, TrainingContent $content): TrainingProgress
    {
        return DB::transaction(function () use ($user, $content) {
            $progress = TrainingProgress::query()->firstOrNew([
                'user_id' => $user->id,
                'training_content_id' => $content->id,
            ]);

            if (! $progress->exists) {
                $progress->started_at = now();
                $progress->save();
            } elseif ($progress->started_at === null) {
                $progress->update(['started_at' => now()]);
            }

            return $progress->refresh();
        });
    }

    public function completeProgress(User $user, TrainingContent $content): TrainingProgress
    {
        return DB::transaction(function () use ($user, $content) {
            $progress = TrainingProgress::query()->firstOrNew([
                'user_id' => $user->id,
                'training_content_id' => $content->id,
            ]);

            if ($progress->started_at === null) {
                $progress->started_at = now();
            }

            $progress->completed_at = now();
            $progress->save();

            return $progress->refresh();
        });
    }

    protected function assertCategoryExists(int $categoryId): void
    {
        TrainingCategory::query()->findOrFail($categoryId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertContentPayload(TrainingContentType $type, array $data): void
    {
        if ($type === TrainingContentType::TEXT && blank($data['content'] ?? null)) {
            throw ValidationException::withMessages([
                'content' => 'O conteúdo textual é obrigatório para este tipo.',
            ]);
        }

        if (in_array($type, [TrainingContentType::VIDEO, TrainingContentType::DOCUMENT], true)
            && blank($data['url'] ?? null)) {
            throw ValidationException::withMessages([
                'url' => 'A URL é obrigatória para vídeos e documentos.',
            ]);
        }
    }
}
