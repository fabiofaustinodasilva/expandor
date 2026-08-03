<?php

namespace App\Domains\Branding\Services;

use App\Domains\Branding\DTOs\BrandPayload;
use App\Domains\Branding\Enums\BrandTheme;

class ThemeService
{
    public function __construct(
        protected BrandingContrastService $contrast,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function normalizeColors(array $input): array
    {
        // Aliases SaaS (primary/secondary/highlight) → tokens internos.
        if (isset($input['primary_color']) && ! isset($input['primary'])) {
            $input['primary'] = $input['primary_color'];
        }
        if (isset($input['secondary_color']) && ! isset($input['secondary'])) {
            $input['secondary'] = $input['secondary_color'];
        }
        if (isset($input['highlight_color']) && ! isset($input['highlight'])) {
            $input['highlight'] = $input['highlight_color'];
        }

        if (isset($input['primary']) && ! isset($input['accent'])) {
            $input['accent'] = $input['primary'];
        }
        if (isset($input['highlight']) && ! isset($input['accent_2'])) {
            $input['accent_2'] = $input['highlight'];
        }
        if (isset($input['secondary']) && ! isset($input['bg_elevated'])) {
            $input['secondary'] = $input['secondary'];
        }

        $defaults = BrandPayload::defaultColors();
        $normalized = [];

        foreach ($defaults as $key => $default) {
            $value = $input[$key] ?? $default;
            $normalized[$key] = $this->sanitizeHex((string) $value, $default);
        }

        $normalized['primary'] = $normalized['primary'] ?: $normalized['accent'];
        $normalized['highlight'] = $normalized['highlight'] ?: $normalized['accent_2'];
        $normalized['secondary'] = $normalized['secondary'] ?: $normalized['bg_elevated'];
        $normalized['accent'] = $normalized['primary'];
        $normalized['accent_2'] = $normalized['highlight'];

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|null>
     */
    public function normalizeFonts(array $input): array
    {
        $defaults = BrandPayload::defaultFonts();

        $family = trim((string) ($input['family'] ?? $defaults['family'] ?? ''));
        $heading = isset($input['heading']) ? trim((string) $input['heading']) : null;

        return [
            'family' => $family !== '' ? $family : $defaults['family'],
            'heading' => $heading !== null && $heading !== '' ? $heading : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|null>
     */
    public function normalizeSocials(array $input): array
    {
        $defaults = BrandPayload::defaultSocials();
        $normalized = [];

        foreach (array_keys($defaults) as $key) {
            $value = isset($input[$key]) ? trim((string) $input[$key]) : null;
            $normalized[$key] = $value !== null && $value !== '' ? $value : null;
        }

        return $normalized;
    }

    public function cssVariables(BrandPayload $brand): string
    {
        $colors = $brand->colors;
        $primary = $brand->primaryColor();
        $secondary = $brand->secondaryColor();
        $highlight = $brand->highlightColor();

        // Superfície de conteúdo (cards/login/rail) = bg_elevated / secondary.
        $surface = $this->contrast->resolve($colors['bg_elevated'] ?? $secondary);
        $page = $this->contrast->resolve($colors['bg'] ?? BrandPayload::defaultColors()['bg']);
        $onPrimary = $this->contrast->resolve($primary);
        $onSoft = $this->contrast->resolve($colors['bg_soft'] ?? $colors['bg']);

        $lines = [
            "--bg: {$colors['bg']};",
            "--bg-elevated: {$colors['bg_elevated']};",
            "--bg-soft: {$colors['bg_soft']};",
            "--border: {$surface['border']};",
            "--text: {$surface['text_primary']};",
            "--text-secondary: {$surface['text_secondary']};",
            "--muted: {$surface['muted_text']};",
            "--text-on-bg: {$page['text_primary']};",
            "--muted-on-bg: {$page['muted_text']};",
            "--text-on-soft: {$onSoft['text_primary']};",
            "--muted-on-soft: {$onSoft['muted_text']};",
            "--button-text: {$onPrimary['button_text']};",
            "--accent: {$primary};",
            "--accent-2: {$highlight};",
            "--primary: {$primary};",
            "--secondary: {$secondary};",
            "--highlight: {$highlight};",
            "--success: {$colors['success']};",
            "--warning: {$colors['warning']};",
            "--brand-contrast-surface: {$surface['css_class']};",
        ];

        return implode("\n            ", $lines);
    }

    public function contrastClass(BrandPayload $brand, string $surface = 'elevated'): string
    {
        $hex = match ($surface) {
            'bg' => $brand->colors['bg'] ?? BrandPayload::defaultColors()['bg'],
            'soft' => $brand->colors['bg_soft'] ?? $brand->colors['bg'],
            'primary' => $brand->primaryColor(),
            default => $brand->colors['bg_elevated'] ?? $brand->secondaryColor(),
        };

        return $this->contrast->resolve($hex)['css_class'];
    }

    public function fontFamily(BrandPayload $brand): string
    {
        return $brand->fonts['family'] ?? BrandPayload::defaultFonts()['family'];
    }

    public function headingFontFamily(BrandPayload $brand): string
    {
        return $brand->fonts['heading'] ?: $this->fontFamily($brand);
    }

    public function bodyBackground(BrandPayload $brand): string
    {
        if ($brand->theme === BrandTheme::Light) {
            return "linear-gradient(180deg, {$brand->colors['bg_soft']}, {$brand->colors['bg']})";
        }

        return "radial-gradient(circle at top left, {$brand->colors['bg_soft']}, {$brand->colors['bg']} 45%)";
    }

    protected function sanitizeHex(string $value, string $fallback): string
    {
        $value = trim($value);

        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $value) === 1) {
            return strtoupper($value);
        }

        return strtoupper($fallback);
    }
}
