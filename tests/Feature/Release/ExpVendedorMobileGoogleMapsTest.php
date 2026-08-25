<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Google Maps basemap on EXP Vendedor Capacitor shell (Leaflet + GoogleMutant).
 * Does not alter the web /map operational stack.
 */
class ExpVendedorMobileGoogleMapsTest extends TestCase
{
    public function test_map_adapter_supports_google_mutant_upgrade_and_osm_fallback(): void
    {
        $adapter = (string) file_get_contents(resource_path('js/mobile/map-adapter.js'));
        $loader = (string) file_get_contents(resource_path('js/mobile/google-maps-loader.js'));

        $this->assertStringContainsString('upgradeToGoogleMaps', $adapter);
        $this->assertStringContainsString('mountLeafletOsmBasemap', $adapter);
        $this->assertStringContainsString("type: 'roadmap'", $adapter);
        $this->assertStringContainsString("type: 'satellite'", $adapter);
        $this->assertStringContainsString('setBasemap', $adapter);
        $this->assertStringContainsString('markerClusterGroup', $adapter);
        $this->assertStringContainsString('recenterGps', $adapter);

        $this->assertStringContainsString('loadGoogleMapsScript', $loader);
        $this->assertStringContainsString('maps.googleapis.com/maps/api/js', $loader);
        $this->assertStringNotContainsString('AIza', $loader);
    }

    public function test_shell_wires_bootstrap_map_config_to_google_upgrade(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));

        $this->assertStringContainsString('maybeUpgradeMobileGoogleBasemap', $shell);
        $this->assertStringContainsString('upgradeToGoogleMaps', $shell);
        $this->assertStringContainsString('browserKey', $shell);
        $this->assertStringContainsString('ensureForegroundPermission', $shell);
        $this->assertStringContainsString('MapAdapter.setBasemap', $shell);
        $this->assertStringContainsString('openCreateSheet(lat, lng, null, \'map-tap\')', $shell);
    }

    public function test_capacitor_csp_allows_google_maps_javascript_api(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $hosts = (string) file_get_contents(base_path('scripts/capacitor-map-csp-hosts.mjs'));

        $this->assertStringContainsString('maps.googleapis.com', $prepare);
        $this->assertStringContainsString('script-src', $prepare);
        $this->assertStringContainsString('https://maps.googleapis.com', $hosts);
        $this->assertStringContainsString('https://maps.gstatic.com', $hosts);
        $this->assertStringNotContainsString('script-src *', $prepare);
    }

    public function test_seller_bundle_imports_google_loader_and_googlemutant(): void
    {
        $seller = (string) file_get_contents(resource_path('js/seller-app.js'));

        $this->assertStringContainsString("import './mobile/google-maps-loader.js'", $seller);
        $this->assertStringContainsString('leaflet.gridlayer.googlemutant', $seller);
        $this->assertStringContainsString('leaflet.markercluster', $seller);
    }

    public function test_web_operational_map_provider_unchanged_by_mobile_files(): void
    {
        $webProvider = (string) file_get_contents(public_path('js/map-provider.js'));
        $webMap = (string) file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString('ExpandorMapProvider', $webProvider);
        $this->assertStringContainsString('createGoogleMapsProvider', $webProvider);
        $this->assertStringContainsString('ExpandorMapProvider.create', $webMap);
        $this->assertStringContainsString('waitForGoogleMaps', $webMap);
    }

    public function test_mobile_google_maps_docs_exist(): void
    {
        $docs = (string) file_get_contents(base_path('docs/mobile-google-maps/README.md'));

        $this->assertStringContainsString('Maps JavaScript API', $docs);
        $this->assertStringContainsString('HTTP referrers', $docs);
        $this->assertStringContainsString('capacitor://localhost', $docs);
        $this->assertStringContainsString('Nunca', $docs);
        $this->assertStringContainsString('browserKey', $docs);
    }
}
