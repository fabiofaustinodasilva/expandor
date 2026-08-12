<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Hotfix — map tiles CSP, brand logo asset, apresentar produtos no mapa.
 */
class ExpVendedorMobileMapHotfixTest extends TestCase
{
    public function test_prepare_shell_csp_allows_explicit_map_tile_hosts(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $hosts = (string) file_get_contents(base_path('scripts/capacitor-map-csp-hosts.mjs'));

        $this->assertStringContainsString('capacitor-map-csp-hosts.mjs', $prepare);
        $this->assertStringContainsString('MAP_TILE_CSP_HOSTS', $hosts);
        $this->assertStringContainsString('https://a.tile.openstreetmap.org', $hosts);
        $this->assertStringContainsString('https://b.tile.openstreetmap.org', $hosts);
        $this->assertStringContainsString('https://c.tile.openstreetmap.org', $hosts);
        $this->assertStringContainsString('https://server.arcgisonline.com', $hosts);
        $this->assertStringContainsString('https://maps.googleapis.com', $hosts);
        $this->assertStringContainsString('https://maps.gstatic.com', $hosts);

        $this->assertStringNotContainsString('img-src *', $prepare);
        $this->assertStringNotContainsString('connect-src *', $prepare);
        $this->assertStringNotContainsString('https://*.tile.openstreetmap.org', $prepare);
        $this->assertStringContainsString('connect-src ${connectSrc}', $prepare);
    }

    public function test_map_adapter_uses_osm_and_esri_tile_urls(): void
    {
        $config = (string) file_get_contents(base_path('resources/js/mobile/map-tile-config.js'));
        $adapter = (string) file_get_contents(base_path('resources/js/mobile/map-adapter.js'));

        $this->assertStringContainsString('tile.openstreetmap.org', $config);
        $this->assertStringContainsString('server.arcgisonline.com', $config);
        $this->assertStringContainsString('OSM_TILE_SUBDOMAINS', $config);
        $this->assertStringContainsString('map-tile-config.js', $adapter);
        $this->assertStringContainsString('setBasemap', $adapter);
        $this->assertStringContainsString('refreshLayout', $adapter);
    }

    public function test_shell_has_present_products_on_map_and_local_logo(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringContainsString('id="map-present-products"', $prepare);
        $this->assertStringContainsString('Apresentar produtos', $prepare);
        $this->assertStringContainsString('map-toolbar--left', $prepare);
        $this->assertStringContainsString('./vendor/exp-vendedor-logo.png', $prepare);
        $this->assertStringContainsString('./vendor/exp-vendedor-icon.png', $prepare);
        $this->assertStringContainsString('exp-vendedor-logo.png', $prepare);
        $this->assertStringNotContainsString('exp-vendedor-logo.svg', $prepare);
        $this->assertStringNotContainsString('logo-exp.svg', $prepare);
        $this->assertStringContainsString('runtime-config.js', $prepare);
    }

    public function test_built_shell_assets_when_present(): void
    {
        $shellIndex = public_path('capacitor-shell/index.html');
        $runtime = public_path('capacitor-shell/runtime-config.js');
        $logo = public_path('capacitor-shell/vendor/exp-vendedor-logo.png');

        if (! is_file($shellIndex) || ! is_file($runtime)) {
            $this->markTestSkipped('capacitor-shell not built in this environment');
        }

        $html = (string) file_get_contents($shellIndex);

        $this->assertStringContainsString('map-present-products', $html);
        $this->assertStringContainsString('./vendor/exp-vendedor-logo.png', $html);
        $this->assertStringContainsString('https://a.tile.openstreetmap.org', $html);
        $this->assertStringNotContainsString('https://*.tile.openstreetmap.org', $html);
        $this->assertStringNotContainsString('${imgSrc}', $html);
        $this->assertStringContainsString('src="./runtime-config.js"', $html);
        $this->assertStringNotContainsString('window.EXPANDOR_API_BASE', $html);

        if (is_file($logo)) {
            $this->assertGreaterThan(20, filesize($logo));
        }
    }
}
