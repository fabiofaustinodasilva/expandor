<?php

namespace App\Domains\AI\Context;

use App\Domains\AI\Context\Contracts\ContextBuilder;
use App\Domains\AI\DTOs\AIContextPayload;
use App\Domains\AI\Enums\AIContextType;
use App\Domains\Training\Models\TrainingCategory;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Models\TrainingProgress;
use App\Tenancy\TenantContext;
use RuntimeException;

class TrainingContext implements ContextBuilder
{
    public function __construct(
        protected TenantContext $tenant,
    ) {}

    public function type(): AIContextType
    {
        return AIContextType::TRAINING;
    }

    public function build(array $options = []): AIContextPayload
    {
        $company = $this->tenant->company();

        if ($company === null) {
            throw new RuntimeException('Tenant company is required to build AI training context.');
        }

        $categories = TrainingCategory::query()->count();
        $contents = TrainingContent::query()->count();
        $completed = TrainingProgress::query()->whereNotNull('completed_at')->count();
        $inProgress = TrainingProgress::query()->whereNull('completed_at')->count();

        $body = implode("\n", [
            'Contexto: Treinamento / Academia (somente dados do tenant atual).',
            'Company ID: '.$company->id,
            'Categorias: '.$categories,
            'Conteúdos: '.$contents,
            'Progressos concluídos: '.$completed,
            'Progressos em andamento: '.$inProgress,
            'Regra: sugira trilhas e reforços; não altere registros de progresso.',
        ]);

        return new AIContextPayload(
            type: $this->type(),
            body: $body,
            companyId: (int) $company->id,
        );
    }
}
