<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Hotfix — logo login transparente + GPS inicial no mapa mobile.
 */
class ExpVendedorMobileGpsHotfixTest extends TestCase
{
    public function test_login_logo_transparency_script_and_png_alpha(): void
    {
        $script = base_path('scripts/ensure-exp-vendedor-logo-transparency.mjs');
        $logo = base_path('public/images/exp-vendedor/exp-vendedor-logo.png');
        $pkg = (string) file_get_contents(base_path('package.json'));

        $this->assertFileExists($script);
        $this->assertStringContainsString('ensure-exp-vendedor-logo-transparency.mjs', $pkg);
        $this->assertStringNotContainsString('exp-vendedor-icon.png', $script);
        $this->assertFileExists($logo);

        $this->assertPngHasAlphaChannel($logo);
    }

    public function test_login_logo_css_has_no_white_background(): void
    {
        $css = (string) file_get_contents(base_path('resources/css/exp-vendedor-shell.css'));

        $this->assertStringContainsString('.login-brand__logo', $css);
        $this->assertStringContainsString('object-fit: contain', $css);
        $this->assertStringContainsString('background: transparent', $css);
        $this->assertStringNotContainsString('border-radius: 0.85rem', $css);
    }

    public function test_shell_login_uses_transparent_logo_png(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringContainsString('./vendor/exp-vendedor-logo.png', $prepare);
        $this->assertStringContainsString('login-brand__logo', $prepare);
        $this->assertStringNotContainsString('exp-vendedor-logo.svg', $prepare);
    }

    public function test_location_service_requests_foreground_permission(): void
    {
        $location = (string) file_get_contents(resource_path('js/mobile/location-service.js'));

        $this->assertStringContainsString('ensureForegroundPermission', $location);
        $this->assertStringContainsString('requestPermissions', $location);
        $this->assertStringContainsString('checkPermissions', $location);
    }

    public function test_bootstrap_attempts_initial_gps_once_without_watch(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $location = (string) file_get_contents(resource_path('js/mobile/location-service.js'));

        $this->assertStringContainsString('tryInitialMapGps', $shell);
        $this->assertStringContainsString('initialMapGpsDone', $shell);
        $this->assertStringContainsString('void tryInitialMapGps()', $shell);
        $this->assertStringContainsString('INITIAL_GPS_TIMEOUT_MS', $shell);
        $this->assertStringContainsString('ensureForegroundPermission', $shell);
        $this->assertStringContainsString('LocationService.getCurrentPosition', $shell);
        $this->assertStringContainsString('MapAdapter.recenterGps', $shell);
        $this->assertStringContainsString('initialMapGpsDone = false', $shell);
        $this->assertStringContainsString('gps-btn', $shell);
        $this->assertStringContainsString('onGps', $shell);
        $this->assertStringNotContainsString('watchPosition', $location);
        $this->assertStringNotContainsString('watchPosition', $shell);
    }

    public function test_gps_denied_uses_fallback_hints_not_hard_fail(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));

        $this->assertStringContainsString('GPS_PERMISSION_HINT', $shell);
        $this->assertStringContainsString('GPS_UNAVAILABLE_HINT', $shell);
        $this->assertStringContainsString('permission_denied', $shell);
        $this->assertStringContainsString('await loadMarkers()', $shell);
    }

    public function test_map_tiles_and_pins_preserved(): void
    {
        $adapter = (string) file_get_contents(resource_path('js/mobile/map-adapter.js'));
        $config = (string) file_get_contents(resource_path('js/mobile/map-tile-config.js'));

        $this->assertStringContainsString('tile.openstreetmap.org', $config);
        $this->assertStringContainsString('renderMarkers', $adapter);
        $this->assertStringContainsString('recenterGps', $adapter);
        $this->assertStringContainsString('gpsCircle', $adapter);
        $this->assertStringContainsString('boundsQuery', $adapter);
    }

    private function assertPngHasAlphaChannel(string $path): void
    {
        $header = (string) file_get_contents($path, false, null, 0, 26);

        $this->assertSame("\x89PNG\r\n\x1a\n", substr($header, 0, 8));
        $this->assertSame(6, ord($header[25]));
    }
}
