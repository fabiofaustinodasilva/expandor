<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * EXP Vendedor — identidade visual PNG oficial no shell e Android.
 */
class ExpVendedorBrandAssetsTest extends TestCase
{
    public function test_official_png_sources_exist(): void
    {
        $logo = base_path('public/images/exp-vendedor/exp-vendedor-logo.png');
        $icon = base_path('public/images/exp-vendedor/exp-vendedor-icon.png');

        $this->assertFileExists($logo);
        $this->assertFileExists($icon);
        $this->assertGreaterThan(1000, filesize($logo));
        $this->assertGreaterThan(1000, filesize($icon));
    }

    public function test_prepare_shell_uses_png_not_svg(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringContainsString('exp-vendedor-logo.png', $prepare);
        $this->assertStringContainsString('exp-vendedor-icon.png', $prepare);
        $this->assertStringContainsString('Bem-vindo ao Expandor', $prepare);
        $this->assertStringNotContainsString('exp-vendedor-logo.svg', $prepare);
        $this->assertStringNotContainsString('logo-exp.svg', $prepare);
        $this->assertStringNotContainsString('login-brand__title', $prepare);
    }

    public function test_android_asset_generator_exists(): void
    {
        $script = (string) file_get_contents(base_path('scripts/generate-exp-vendedor-android-assets.mjs'));
        $pkg = (string) file_get_contents(base_path('package.json'));

        $this->assertStringContainsString('generate-exp-vendedor-android-assets.mjs', $pkg);
        $this->assertStringContainsString('ic_launcher.png', $script);
        $this->assertStringContainsString('splash.png', $script);
        $this->assertStringContainsString('exp-vendedor-icon.png', $script);
    }

    public function test_android_splash_and_launcher_brand_colors(): void
    {
        $styles = (string) file_get_contents(base_path('android/app/src/main/res/values/styles.xml'));
        $colors = (string) file_get_contents(base_path('android/app/src/main/res/values/ic_launcher_background.xml'));

        $this->assertStringContainsString('Theme.SplashScreen', $styles);
        $this->assertStringContainsString('windowSplashScreenBackground', $styles);
        $this->assertStringContainsString('splash_background', $styles);
        $this->assertStringContainsString('#1E4A8C', $colors);
    }

    public function test_built_shell_png_assets_when_present(): void
    {
        $logo = public_path('capacitor-shell/vendor/exp-vendedor-logo.png');
        $icon = public_path('capacitor-shell/vendor/exp-vendedor-icon.png');
        $html = public_path('capacitor-shell/index.html');

        if (! is_file($html)) {
            $this->markTestSkipped('capacitor-shell not built');
        }

        $contents = (string) file_get_contents($html);
        $this->assertStringContainsString('./vendor/exp-vendedor-logo.png', $contents);
        $this->assertStringContainsString('./vendor/exp-vendedor-icon.png', $contents);
        $this->assertStringNotContainsString('exp-vendedor-logo.svg', $contents);
        $this->assertStringNotContainsString('logo-exp.svg', $contents);

        if (is_file($logo) && is_file($icon)) {
            $this->assertGreaterThan(1000, filesize($logo));
            $this->assertGreaterThan(1000, filesize($icon));
            $this->assertSame(6, ord((string) file_get_contents($logo, false, null, 25, 1)));
        }
    }
}
