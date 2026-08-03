<?php

namespace App\Domains\AI\Contracts;

use App\Domains\AI\DTOs\AIAnalyzeResult;
use App\Domains\AI\DTOs\AIChatResult;

interface AIProviderContract
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function chat(string $systemPrompt, string $userMessage, array $options = []): AIChatResult;

    /**
     * @param  array<string, mixed>  $options
     */
    public function analyze(string $systemPrompt, string $payload, array $options = []): AIAnalyzeResult;

    public function name(): string;
}
