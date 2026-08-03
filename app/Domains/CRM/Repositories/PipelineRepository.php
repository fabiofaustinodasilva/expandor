<?php

namespace App\Domains\CRM\Repositories;

use App\Domains\CRM\Models\PipelineStage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PipelineRepository
{
    public function stagesOrdered(): Collection
    {
        return PipelineStage::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();
    }

    public function allStages(): Collection
    {
        return PipelineStage::query()->orderBy('position')->get();
    }

    public function firstOpenStage(): ?PipelineStage
    {
        return PipelineStage::query()
            ->where('is_active', true)
            ->where('is_won', false)
            ->where('is_lost', false)
            ->orderBy('position')
            ->first();
    }

    public function wonStage(): ?PipelineStage
    {
        return PipelineStage::query()->where('is_won', true)->orderBy('position')->first();
    }

    public function lostStage(): ?PipelineStage
    {
        return PipelineStage::query()->where('is_lost', true)->orderBy('position')->first();
    }

    /**
     * @param  list<array{name: string, slug?: string, color?: string|null, is_won?: bool, is_lost?: bool}>  $defaults
     * @return Collection<int, PipelineStage>
     */
    public function seedDefaultsIfEmpty(array $defaults): Collection
    {
        if (PipelineStage::query()->exists()) {
            return $this->stagesOrdered();
        }

        foreach ($defaults as $index => $stage) {
            PipelineStage::query()->create([
                'name' => $stage['name'],
                'slug' => $stage['slug'] ?? Str::slug($stage['name']),
                'position' => $index + 1,
                'color' => $stage['color'] ?? null,
                'is_won' => (bool) ($stage['is_won'] ?? false),
                'is_lost' => (bool) ($stage['is_lost'] ?? false),
                'is_active' => true,
            ]);
        }

        return $this->stagesOrdered();
    }
}
