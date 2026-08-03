<?php

namespace App\Domains\AI\DTOs;

class AIAnalyzeResult
{
    /**
     * @param  array<string, mixed>  $insights
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $summary,
        public readonly array $insights = [],
        public readonly ?int $tokensUsed = null,
        public readonly string $provider = 'unknown',
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $insights
     * @param  array<string, mixed>  $raw
     */
    public static function ok(
        string $summary,
        array $insights = [],
        ?int $tokensUsed = null,
        string $provider = 'openai',
        array $raw = [],
    ): self {
        return new self(
            success: true,
            summary: $summary,
            insights: $insights,
            tokensUsed: $tokensUsed,
            provider: $provider,
            raw: $raw,
        );
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fail(string $error, string $provider = 'openai', array $raw = []): self
    {
        return new self(
            success: false,
            summary: '',
            provider: $provider,
            error: $error,
            raw: $raw,
        );
    }
}
