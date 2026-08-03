<?php

namespace App\Domains\AI\Providers;

use App\Domains\AI\Contracts\AIProviderContract;
use InvalidArgumentException;

class ProviderFactory
{
    public function make(?string $driver = null): AIProviderContract
    {
        $driver = strtolower(trim($driver ?: (string) config('ai.default', 'openai')));

        return match ($driver) {
            'openai' => $this->makeOpenAI(),
            default => throw new InvalidArgumentException("Unsupported AI provider [{$driver}]."),
        };
    }

    protected function makeOpenAI(): OpenAIProvider
    {
        return new OpenAIProvider((array) config('ai.providers.openai', []));
    }
}
