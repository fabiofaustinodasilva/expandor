<?php

namespace App\Domains\CRM\Services;

use App\Domains\CRM\Repositories\PipelineRepository;
use Illuminate\Support\Collection;

class PipelineService
{
    public function __construct(
        protected PipelineRepository $repository,
    ) {}

    public function ensureDefaultStages(): Collection
    {
        return $this->repository->seedDefaultsIfEmpty([
            ['name' => 'Novo', 'slug' => 'novo', 'color' => '#64748b'],
            ['name' => 'Qualificação', 'slug' => 'qualificacao', 'color' => '#3b82f6'],
            ['name' => 'Proposta', 'slug' => 'proposta', 'color' => '#8b5cf6'],
            ['name' => 'Negociação', 'slug' => 'negociacao', 'color' => '#f59e0b'],
            ['name' => 'Ganho', 'slug' => 'ganho', 'color' => '#22c55e', 'is_won' => true],
            ['name' => 'Perdido', 'slug' => 'perdido', 'color' => '#ef4444', 'is_lost' => true],
        ]);
    }

    public function stages(): Collection
    {
        $this->ensureDefaultStages();

        return $this->repository->stagesOrdered();
    }
}
