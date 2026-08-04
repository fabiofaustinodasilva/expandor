<?php

namespace App\Domains\Marketplace\Growth\DTOs;

readonly class UtmAttribution
{
    public function __construct(
        public ?string $source = null,
        public ?string $medium = null,
        public ?string $campaign = null,
        public ?string $term = null,
        public ?string $content = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            source: self::clean($input['utm_source'] ?? null),
            medium: self::clean($input['utm_medium'] ?? null),
            campaign: self::clean($input['utm_campaign'] ?? null),
            term: self::clean($input['utm_term'] ?? null),
            content: self::clean($input['utm_content'] ?? null),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'utm_source' => $this->source,
            'utm_medium' => $this->medium,
            'utm_campaign' => $this->campaign,
            'utm_term' => $this->term,
            'utm_content' => $this->content,
        ];
    }

    public function hasAny(): bool
    {
        return filled($this->source)
            || filled($this->medium)
            || filled($this->campaign)
            || filled($this->term)
            || filled($this->content);
    }

    protected static function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed !== '' ? substr($trimmed, 0, 180) : null;
    }
}
