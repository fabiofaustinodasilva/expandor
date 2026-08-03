<?php

namespace App\Domains\Branding\Services;

/**
 * Contraste automático SaaS — texto legível em qualquer combinação de cores de branding.
 */
class BrandingContrastService
{
    public const LIGHT_TEXT = '#F8FAFC';

    public const LIGHT_TEXT_SECONDARY = '#E2E8F0';

    public const LIGHT_MUTED = '#94A3B8';

    public const DARK_TEXT = '#0F172A';

    public const DARK_TEXT_SECONDARY = '#1E293B';

    public const DARK_MUTED = '#64748B';

    public const LIGHT_BORDER = '#D0D7E2';

    public const DARK_BORDER = '#2A3142';

    /** Limiar WCAG-ish: luminância relativa acima = superfície clara. */
    public const LIGHT_LUMINANCE_THRESHOLD = 0.45;

    /**
     * @return array{
     *     text_primary: string,
     *     text_secondary: string,
     *     muted_text: string,
     *     button_text: string,
     *     border: string,
     *     is_light: bool,
     *     css_class: string
     * }
     */
    public function resolve(string $backgroundHex): array
    {
        $hex = $this->normalizeHex($backgroundHex) ?? '#0F1117';
        $isLight = $this->isLight($hex);

        return [
            'text_primary' => $isLight ? self::DARK_TEXT : self::LIGHT_TEXT,
            'text_secondary' => $isLight ? self::DARK_TEXT_SECONDARY : self::LIGHT_TEXT_SECONDARY,
            'muted_text' => $isLight ? self::DARK_MUTED : self::LIGHT_MUTED,
            'button_text' => $isLight ? self::DARK_TEXT : '#FFFFFF',
            'border' => $isLight ? self::LIGHT_BORDER : self::DARK_BORDER,
            'is_light' => $isLight,
            'css_class' => $isLight ? 'brand-contrast-light' : 'brand-contrast-dark',
        ];
    }

    public function textPrimaryOn(string $backgroundHex): string
    {
        return $this->resolve($backgroundHex)['text_primary'];
    }

    public function textSecondaryOn(string $backgroundHex): string
    {
        return $this->resolve($backgroundHex)['text_secondary'];
    }

    public function mutedTextOn(string $backgroundHex): string
    {
        return $this->resolve($backgroundHex)['muted_text'];
    }

    public function buttonTextOn(string $buttonBackgroundHex): string
    {
        return $this->resolve($buttonBackgroundHex)['button_text'];
    }

    public function isLight(string $hex): bool
    {
        $normalized = $this->normalizeHex($hex);
        if ($normalized === null) {
            return false;
        }

        return $this->relativeLuminance($normalized) >= self::LIGHT_LUMINANCE_THRESHOLD;
    }

    public function isDark(string $hex): bool
    {
        return ! $this->isLight($hex);
    }

    public function relativeLuminance(string $hex): float
    {
        [$r, $g, $b] = $this->hexToRgb($hex);

        $channels = array_map(static function (int $channel): float {
            $c = $channel / 255;

            return $c <= 0.03928
                ? $c / 12.92
                : (($c + 0.055) / 1.055) ** 2.4;
        }, [$r, $g, $b]);

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public function hexToRgb(string $hex): array
    {
        $normalized = $this->normalizeHex($hex) ?? '#000000';
        $value = substr($normalized, 1);

        return [
            hexdec(substr($value, 0, 2)),
            hexdec(substr($value, 2, 2)),
            hexdec(substr($value, 4, 2)),
        ];
    }

    public function normalizeHex(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('/^#([A-Fa-f0-9]{3})$/', $value, $m) === 1) {
            $short = strtoupper($m[1]);

            return '#'.$short[0].$short[0].$short[1].$short[1].$short[2].$short[2];
        }

        if (preg_match('/^#([A-Fa-f0-9]{6})$/', $value, $m) === 1) {
            return '#'.strtoupper($m[1]);
        }

        return null;
    }
}
