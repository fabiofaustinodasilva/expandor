<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Hotfix 8.2.34 — CSP script-src 'self' blocks inline EXPANDOR_API_BASE.
 */
class CapacitorRuntimeConfigCspHotfixTest extends TestCase
{
    public function test_prepare_emits_external_runtime_config_under_strict_csp(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringContainsString('runtime-config.js', $prepare);
        $this->assertStringContainsString('writeFileSync(join(outDir, \'runtime-config.js\')', $prepare);
        $this->assertStringContainsString('src="./runtime-config.js"', $prepare);
        $this->assertStringContainsString('src="./vendor/seller-app.js"', $prepare);
        $htmlStart = strpos($prepare, 'const html = ');
        $this->assertNotFalse($htmlStart);
        $htmlChunk = substr($prepare, $htmlStart);
        $runtimeInHtml = strpos($htmlChunk, 'src="./runtime-config.js"');
        $appInHtml = strpos($htmlChunk, 'src="./vendor/seller-app.js"');
        $this->assertNotFalse($runtimeInHtml);
        $this->assertNotFalse($appInHtml);
        $this->assertLessThan($appInHtml, $runtimeInHtml);

        $this->assertStringContainsString("script-src 'self'", $prepare);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $prepare);
        $this->assertStringNotContainsString('script-src *', $prepare);
        $this->assertStringNotContainsString('unsafe-eval', $prepare);
        $this->assertStringNotContainsString('<script>\n        window.EXPANDOR_API_BASE', $prepare);
        $this->assertStringNotContainsString("frame-ancestors 'none'", $prepare);
        $this->assertStringContainsString('frame-ancestors is ignored on <meta>', $prepare);
        $this->assertStringContainsString('connect-src ${connectSrc}', $prepare);

        $this->assertDoesNotMatchRegularExpression('/APP_KEY|SMTP|DB_PASSWORD|MERCADO.?PAGO/i', $prepare);
    }

    public function test_built_shell_runtime_config_when_present(): void
    {
        $shellIndex = public_path('capacitor-shell/index.html');
        $runtime = public_path('capacitor-shell/runtime-config.js');

        if (! is_file($shellIndex) || ! is_file($runtime)) {
            $this->markTestSkipped('capacitor-shell not built in this environment');
        }

        $html = (string) file_get_contents($shellIndex);
        $js = (string) file_get_contents($runtime);

        $this->assertStringContainsString('src="./runtime-config.js"', $html);
        $this->assertStringContainsString('src="./vendor/seller-app.js"', $html);
        $this->assertStringNotContainsString('window.EXPANDOR_API_BASE', $html);
        $this->assertStringContainsString("script-src 'self'", $html);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $html);
        $this->assertMatchesRegularExpression('/window\.EXPANDOR_API_BASE\s*=\s*"[^"]+"/', $js);
        $this->assertDoesNotMatchRegularExpression('/APP_KEY|SMTP|password|secret|Bearer\s+[A-Za-z0-9]/i', $js);

        $runtimePos = strpos($html, 'runtime-config.js');
        $appPos = strpos($html, 'vendor/seller-app.js');
        $this->assertNotFalse($runtimePos);
        $this->assertNotFalse($appPos);
        $this->assertLessThan($appPos, $runtimePos);
    }
}
