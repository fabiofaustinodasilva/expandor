<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Sprint 8.2.32 — Capacitor bootstrap + local seller/app assets.
 */
class Sprint8232CapacitorBootstrapAssetsTest extends TestCase
{
    public function test_capacitor_dependencies_exist(): void
    {
        $pkg = (string) file_get_contents(base_path('package.json'));
        $this->assertStringContainsString('"@capacitor/core"', $pkg);
        $this->assertStringContainsString('"@capacitor/cli"', $pkg);
        $this->assertStringContainsString('"@capacitor/android"', $pkg);
        $this->assertStringContainsString('"leaflet":', $pkg);
        $this->assertStringContainsString('"leaflet.markercluster"', $pkg);
        $this->assertStringContainsString('"leaflet.gridlayer.googlemutant"', $pkg);
        $this->assertStringContainsString('"lucide"', $pkg);
    }

    public function test_capacitor_config_app_id_and_webdir(): void
    {
        $config = (string) file_get_contents(base_path('capacitor.config.json'));
        $decoded = json_decode($config, true);
        $this->assertIsArray($decoded);
        $this->assertSame('br.com.expandor.app', $decoded['appId']);
        $this->assertSame('Expandor', $decoded['appName']);
        $this->assertSame('public/capacitor-shell', $decoded['webDir']);
        $this->assertArrayNotHasKey('server', $decoded);
        $this->assertStringNotContainsString('https://', $config);
        $this->assertDoesNotMatchRegularExpression('/APP_KEY|DB_PASSWORD|SMTP|MERCADO.?PAGO/i', $config);
        $example = (string) file_get_contents(base_path('.env.example'));
        $this->assertStringContainsString('CAP_SERVER_URL', $example);
    }

    public function test_tailwind_seller_does_not_use_cdn(): void
    {
        $operational = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $operational);
        $this->assertStringContainsString('layouts.partials.seller-vendor', $operational);
        $partial = (string) file_get_contents(resource_path('views/layouts/partials/seller-vendor.blade.php'));
        $this->assertStringContainsString('vendor/expandor/seller-app.css', $partial);
        $this->assertFileExists(resource_path('css/seller-app.css'));
        $css = (string) file_get_contents(resource_path('css/seller-app.css'));
        $this->assertStringContainsString("@import 'tailwindcss'", $css);
        $this->assertStringContainsString('@source', $css);
        $this->assertStringContainsString('../**/*.blade.php', $css);
        $this->assertStringContainsString('../../public/js/**/*.js', $css);
    }

    public function test_lucide_leaflet_cluster_mutant_are_local(): void
    {
        $operational = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $app = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));
        $map = (string) file_get_contents(resource_path('views/maps/index.blade.php'));
        $js = (string) file_get_contents(resource_path('js/seller-app.js'));

        $this->assertStringNotContainsString('unpkg.com/lucide', $operational);
        $this->assertStringNotContainsString('unpkg.com/lucide', $app);
        $this->assertStringNotContainsString('unpkg.com/leaflet', $map);
        $this->assertStringNotContainsString('leaflet.markercluster@', $map);
        $this->assertStringNotContainsString('leaflet.gridlayer.googlemutant', $map);

        $this->assertStringContainsString("from 'leaflet'", $js);
        $this->assertStringContainsString("import 'leaflet.markercluster'", $js);
        $this->assertStringContainsString("import 'leaflet.gridlayer.googlemutant'", $js);
        $this->assertStringContainsString("from 'lucide'", $js);
        $this->assertStringContainsString('window.L = L', $js);
        $this->assertStringContainsString('window.lucide', $js);
        $this->assertStringContainsString('createIconsCompat', $js);
        $this->assertStringContainsString('createIcons', $js);
        $this->assertStringContainsString('maps.googleapis.com/maps/api/js', $map);
    }

    public function test_shell_has_viewport_fit_cover_and_safe_area(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $this->assertStringContainsString('viewport-fit=cover', $prepare);
        $this->assertStringContainsString('safe-area-inset-top', $prepare);
        $this->assertStringContainsString('Bootstrap Capacitor OK', $prepare);

        $operational = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $this->assertStringContainsString('viewport-fit=cover', $operational);

        $ui = (string) file_get_contents(public_path('css/client-ui.css'));
        $this->assertStringContainsString('--safe-area-top', $ui);
        $this->assertStringContainsString('--safe-area-bottom', $ui);
        $this->assertStringContainsString('env(safe-area-inset-top', $ui);
        $this->assertStringContainsString('env(safe-area-inset-bottom', $ui);
        $this->assertStringContainsString('env(safe-area-inset-right', $ui);
        $this->assertStringContainsString('env(safe-area-inset-left', $ui);
    }

    public function test_no_service_worker_added(): void
    {
        $this->assertFileDoesNotExist(public_path('sw.js'));
        $this->assertFileDoesNotExist(base_path('public/service-worker.js'));
        $pkg = (string) file_get_contents(base_path('package.json'));
        $this->assertStringNotContainsString('workbox', $pkg);
        $this->assertStringNotContainsString('vite-plugin-pwa', $pkg);
    }

    public function test_package_scripts_and_gitignore(): void
    {
        $pkg = json_decode((string) file_get_contents(base_path('package.json')), true);
        $this->assertIsArray($pkg['scripts']);
        foreach (['build', 'dev', 'cap:sync', 'cap:android', 'cap:open:android', 'build:seller'] as $script) {
            $this->assertArrayHasKey($script, $pkg['scripts'], $script);
        }
        $this->assertStringContainsString('vite.seller.config.js', $pkg['scripts']['build']);
        $this->assertStringContainsString('prepare-capacitor-shell.mjs', $pkg['scripts']['build']);

        $ignore = (string) file_get_contents(base_path('.gitignore'));
        $this->assertStringContainsString('/public/capacitor-shell', $ignore);
        $this->assertStringContainsString('/public/vendor/expandor', $ignore);
        $this->assertStringContainsString('*.keystore', $ignore);
        $this->assertStringContainsString('android/local.properties', $ignore);
        $this->assertStringContainsString('/.env', $ignore);
    }

    public function test_docs_inventory_android_ios_offline_exist(): void
    {
        $dir = base_path('docs/sprint-8232-capacitor-bootstrap-assets');
        foreach ([
            'README.md',
            'ASSET-INVENTORY.md',
            'BUILD-PIPELINE.md',
            'TAILWIND.md',
            'LUCIDE.md',
            'LEAFLET.md',
            'MARKERCLUSTER.md',
            'GOOGLEMUTANT.md',
            'CAPACITOR-CONFIG.md',
            'ANDROID.md',
            'IOS.md',
            'SAFE-AREA.md',
            'CSP-CORS-NOTES.md',
            'SECURITY.md',
            'OFFLINE-SHELL.md',
            'TEST-REPORT.md',
            'CHANGELOG.md',
        ] as $file) {
            $this->assertFileExists($dir.DIRECTORY_SEPARATOR.$file, $file);
        }

        $android = mb_strtolower((string) file_get_contents($dir.DIRECTORY_SEPARATOR.'ANDROID.md'));
        $ios = mb_strtolower((string) file_get_contents($dir.DIRECTORY_SEPARATOR.'IOS.md'));
        $offline = mb_strtolower((string) file_get_contents($dir.DIRECTORY_SEPARATOR.'OFFLINE-SHELL.md'));
        $this->assertStringContainsString('android', $android);
        $this->assertStringContainsString('macos', $ios);
        $this->assertStringContainsString('xcode', $ios);
        $this->assertStringContainsString('cdn', $offline);
        $this->assertSame([], glob(base_path('database/migrations/*8232*')) ?: []);
        $this->assertDirectoryExists(base_path('android'));
        $this->assertFileExists(base_path('android/app/src/main/AndroidManifest.xml'));
        $this->assertDirectoryDoesNotExist(base_path('ios'));
    }
}
