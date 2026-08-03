<?php

namespace App\Domains\AI\Context;

use App\Domains\AI\Context\Contracts\ContextBuilder;
use App\Domains\AI\Enums\AIContextType;

class ContextBuilderFactory
{
    public function make(AIContextType|string $type): ContextBuilder
    {
        $type = $type instanceof AIContextType
            ? $type
            : AIContextType::from($type);

        return match ($type) {
            AIContextType::COMPANY => app(CompanyContext::class),
            AIContextType::SALES => app(SalesContext::class),
            AIContextType::TRAINING => app(TrainingContext::class),
        };
    }
}
