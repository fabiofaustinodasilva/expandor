<?php

namespace App\Domains\Onboarding\DTOs;

readonly class TrialBannerDTO
{
    public function __construct(
        public bool $show,
        public string $variant, // active|tomorrow|expired|none
        public string $message,
        public ?int $daysRemaining,
        public bool $isExpired,
        public ?string $convertUrl,
    ) {}

    public static function hidden(): self
    {
        return new self(
            show: false,
            variant: 'none',
            message: '',
            daysRemaining: null,
            isExpired: false,
            convertUrl: null,
        );
    }
}
