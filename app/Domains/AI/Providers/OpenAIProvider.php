<?php

namespace App\Domains\AI\Providers;

use App\Domains\AI\Contracts\AIProviderContract;
use App\Domains\AI\DTOs\AIAnalyzeResult;
use App\Domains\AI\DTOs\AIChatResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAIProvider implements AIProviderContract
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {}

    public function name(): string
    {
        return 'openai';
    }

    public function chat(string $systemPrompt, string $userMessage, array $options = []): AIChatResult
    {
        try {
            $response = $this->request([
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage,
                ],
            ], $options);

            if ($response === null) {
                return AIChatResult::fail('OpenAI API key is not configured.', $this->name());
            }

            if (! $response->successful()) {
                return AIChatResult::fail(
                    'OpenAI chat failed: HTTP '.$response->status(),
                    $this->name(),
                    $response->json() ?? ['body' => $response->body()]
                );
            }

            $payload = $response->json() ?? [];
            $content = (string) data_get($payload, 'choices.0.message.content', '');

            return AIChatResult::ok(
                content: trim($content) !== '' ? $content : 'Sem resposta do provedor.',
                tokensUsed: $this->tokensFromPayload($payload),
                provider: $this->name(),
                raw: $payload,
            );
        } catch (ConnectionException $exception) {
            return AIChatResult::fail('OpenAI unreachable: '.$exception->getMessage(), $this->name());
        } catch (Throwable $exception) {
            return AIChatResult::fail('OpenAI chat error: '.$exception->getMessage(), $this->name());
        }
    }

    public function analyze(string $systemPrompt, string $payload, array $options = []): AIAnalyzeResult
    {
        $result = $this->chat(
            $systemPrompt,
            "Analise o seguinte contexto e responda em português com insights acionáveis (apenas sugestões):\n\n".$payload,
            $options
        );

        if (! $result->success) {
            return AIAnalyzeResult::fail($result->error ?? 'Analyze failed.', $this->name(), $result->raw);
        }

        return AIAnalyzeResult::ok(
            summary: $result->content,
            insights: ['suggestion' => $result->content],
            tokensUsed: $result->tokensUsed,
            provider: $this->name(),
            raw: $result->raw,
        );
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    protected function request(array $messages, array $options = []): mixed
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');

        if ($apiKey === '') {
            return null;
        }

        return Http::baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://api.openai.com/v1'), '/'))
            ->timeout((int) ($this->config['timeout'] ?? 30))
            ->withToken($apiKey)
            ->acceptJson()
            ->post((string) ($this->config['chat_endpoint'] ?? '/chat/completions'), [
                'model' => (string) ($options['model'] ?? $this->config['model'] ?? 'gpt-4o-mini'),
                'messages' => $messages,
                'temperature' => (float) ($options['temperature'] ?? 0.3),
            ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function tokensFromPayload(array $payload): ?int
    {
        $total = data_get($payload, 'usage.total_tokens');

        return is_numeric($total) ? (int) $total : null;
    }
}
