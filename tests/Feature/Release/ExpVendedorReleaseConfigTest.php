<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

class ExpVendedorReleaseConfigTest extends TestCase
{
    public function test_self_test_covers_production_and_blocked_hosts(): void
    {
        [$code, $output] = $this->node([
            base_path('scripts/exp-vendedor-release-config.mjs'),
            '--self-test',
        ]);

        $this->assertSame(0, $code, $output);
        $this->assertStringContainsString('OK A production https passes', $output);
        $this->assertStringContainsString('OK B 127.0.0.1 fails', $output);
        $this->assertStringContainsString('OK C localhost fails', $output);
        $this->assertStringContainsString('OK D 10.0.2.2 fails', $output);
        $this->assertStringContainsString('OK E http in release fails', $output);
        $this->assertStringContainsString('OK F good API + local web fails', $output);
        $this->assertStringContainsString('OK G missing runtime fields fail', $output);
        $this->assertStringContainsString('OK H QA without CAP_API_URL fails', $output);
    }

    public function test_verifier_rejects_missing_runtime_file(): void
    {
        [$code, $output] = $this->node([
            base_path('scripts/verify-exp-vendedor-release-config.mjs'),
            '--runtime',
            base_path('storage/framework/testing/missing-runtime-config.js'),
        ]);

        $this->assertNotSame(0, $code);
        $this->assertStringContainsString('runtime-config ausente', $output);
    }

    public function test_verifier_accepts_production_runtime_and_rejects_localhost(): void
    {
        $ok = sys_get_temp_dir().DIRECTORY_SEPARATOR.'exp-ok-runtime.js';
        $bad = sys_get_temp_dir().DIRECTORY_SEPARATOR.'exp-bad-runtime.js';
        file_put_contents($ok, "window.EXPANDOR_API_BASE = \"https://expandor.unicanetwork.com.br\";\nwindow.EXPANDOR_WEB_ORIGIN = \"https://expandor.unicanetwork.com.br\";\n");
        file_put_contents($bad, "window.EXPANDOR_API_BASE = \"http://127.0.0.1:8000\";\nwindow.EXPANDOR_WEB_ORIGIN = \"http://127.0.0.1:8000\";\n");

        [$okCode, $okOut] = $this->node([
            base_path('scripts/verify-exp-vendedor-release-config.mjs'),
            '--runtime',
            $ok,
        ]);
        [$badCode, $badOut] = $this->node([
            base_path('scripts/verify-exp-vendedor-release-config.mjs'),
            '--runtime',
            $bad,
        ]);

        $this->assertSame(0, $okCode, $okOut);
        $this->assertStringContainsString('Config OK', $okOut);
        $this->assertNotSame(0, $badCode);
        $this->assertStringContainsString('127.0.0.1', $badOut);
    }

    public function test_prepare_and_qa_command_do_not_fallback_app_url_in_distribution(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $pkg = (string) file_get_contents(base_path('package.json'));

        $this->assertStringContainsString('resolveBakeUrls', $prepare);
        $this->assertStringContainsString('will not fall back to APP_URL', $prepare);
        $this->assertStringContainsString('EXPANDOR_BUILD_MODE', $prepare);
        $this->assertStringContainsString('EXPANDOR_GIT_HASH', $prepare);
        $this->assertStringContainsString('"android:qa"', $pkg);
        $this->assertStringContainsString('scripts/android-qa.mjs', $pkg);
    }

    /**
     * @param  list<string>  $args
     * @return array{0: int, 1: string}
     */
    private function node(array $args): array
    {
        $cmd = 'node '.implode(' ', array_map('escapeshellarg', $args));
        $output = [];
        $code = 0;
        exec($cmd.' 2>&1', $output, $code);

        return [$code, implode("\n", $output)];
    }
}
