<?php

namespace Tests\Support;

use App\Domains\AI\Contracts\AIProviderContract;
use App\Domains\AI\DTOs\AIAnalyzeResult;
use App\Domains\AI\DTOs\AIChatResult;

class FakeAIProvider implements AIProviderContract
{
    /** @var list<array{system: string, user: string, options: array<string, mixed>}> */
    public array $chatCalls = [];

    /** @var list<array{system: string, payload: string, options: array<string, mixed>}> */
    public array $analyzeCalls = [];

    public string $chatResponse = 'Sugestão fake: priorize retornos pendentes sem alterar dados.';

    public string $analyzeResponse = 'Análise fake: foque nos setores com mais interesse.';

    public ?int $tokensUsed = 12;

    public bool $shouldFail = false;

    public function name(): string
    {
        return 'fake';
    }

    public function chat(string $systemPrompt, string $userMessage, array $options = []): AIChatResult
    {
        $this->chatCalls[] = [
            'system' => $systemPrompt,
            'user' => $userMessage,
            'options' => $options,
        ];

        if ($this->shouldFail) {
            return AIChatResult::fail('Simulated AI failure.', $this->name());
        }

        return AIChatResult::ok(
            content: $this->chatResponse,
            tokensUsed: $this->tokensUsed,
            provider: $this->name(),
        );
    }

    public function analyze(string $systemPrompt, string $payload, array $options = []): AIAnalyzeResult
    {
        $this->analyzeCalls[] = [
            'system' => $systemPrompt,
            'payload' => $payload,
            'options' => $options,
        ];

        if ($this->shouldFail) {
            return AIAnalyzeResult::fail('Simulated analyze failure.', $this->name());
        }

        return AIAnalyzeResult::ok(
            summary: $this->analyzeResponse,
            insights: ['suggestion' => $this->analyzeResponse],
            tokensUsed: $this->tokensUsed,
            provider: $this->name(),
        );
    }
}
