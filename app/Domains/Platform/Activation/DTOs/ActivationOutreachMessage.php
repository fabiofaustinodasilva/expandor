<?php

namespace App\Domains\Platform\Activation\DTOs;

readonly class ActivationOutreachMessage
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $template,
        public string $subject,
        public string $body,
        public ?string $ctaUrl = null,
        public array $metadata = [],
    ) {}
}
