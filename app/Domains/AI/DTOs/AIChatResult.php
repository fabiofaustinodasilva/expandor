<?php

namespace App\Domains\AI\DTOs;

class AIChatResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $content,
        public readonly ?int $tokensUsed = null,
        public readonly string $provider = 'unknown',
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function ok(
        string $content,
        ?int $tokensUsed = null,
        string $provider = 'openai',
        array $raw = [],
    ): self {
        return new self(
            success: true,
            content: $content,
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
            content: '',
            provider: $provider,
            error: $error,
            raw: $raw,
        );
    }
}
