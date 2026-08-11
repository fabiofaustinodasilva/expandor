<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Support\ClientArea\ClientNav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.31 — Capacitor readiness (docs + inventory). No Capacitor install.
 */
class Sprint8231CapacitorReadinessTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_readiness_docs_exist(): void
    {
        $dir = base_path('docs/sprint-8231-capacitor-app-readiness');
        foreach ([
            'README.md',
            'AUDIT.md',
            'ARCHITECTURE-DECISION.md',
            'AUTH.md',
            'SESSION.md',
            'API-INVENTORY.md',
            'ASSETS-CDN.md',
            'MAP-GPS.md',
            'OFFLINE.md',
            'SYNC.md',
            'IDEMPOTENCY.md',
            'LOCAL-STORAGE.md',
            'SECURITY.md',
            'DEEP-LINKS.md',
            'NETWORK-UX.md',
            'ANDROID.md',
            'IOS.md',
            'PRIVACY.md',
            'APP-READINESS-MATRIX.md',
            'IMPLEMENTATION-ROADMAP.md',
            'TEST-REPORT.md',
            'CHANGELOG.md',
        ] as $file) {
            $this->assertFileExists($dir.DIRECTORY_SEPARATOR.$file, $file);
        }
    }

    public function test_seller_routes_inventory(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8231 Nav');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-8231@test']);
        $labels = array_column(ClientNav::railItems($seller), 'route');

        $this->assertContains('map.index', $labels);
        $this->assertContains('follow-ups.index', $labels);
        $this->assertContains('customers.index', $labels);
        $this->assertContains('dashboard', $labels);
        $this->assertContains('commissions.index', $labels);
        $this->assertContains('sales-app.products.present', $labels);

        foreach (['login', 'logout', 'password.request', 'password.reset', 'profile.edit', 'operations.more'] as $name) {
            $this->assertTrue(\Illuminate\Support\Facades\Route::has($name), $name);
        }
    }

    public function test_api_inventory_ready_and_missing(): void
    {
        $api = (string) file_get_contents(base_path('routes/api.php'));
        $this->assertStringContainsString("/auth/login", $api);
        $this->assertStringContainsString('mobile/v1', $api);
        $this->assertStringContainsString('/maps/markers', $api);
        $this->assertStringContainsString('/pending-sync', $api);
        $this->assertStringContainsString("prefix('v1')", $api);

        $web = (string) file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString('map.points.store', $web);
        $this->assertStringContainsString('map.visits.store', $web);
        $this->assertStringContainsString('map.first-approach', $web);

        $inventory = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/API-INVENTORY.md'));
        $this->assertStringContainsString('MISSING', $inventory);
        $this->assertStringContainsString('READY', $inventory);
        $this->assertStringContainsString('PARTIAL', $inventory);
    }

    public function test_cdn_dependency_inventory(): void
    {
        $operational = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $map = (string) file_get_contents(resource_path('views/maps/index.blade.php'));
        $this->assertStringContainsString('cdn.tailwindcss.com', $operational);
        $this->assertStringContainsString('unpkg.com/lucide@0.469.0', $operational);
        $this->assertStringContainsString('unpkg.com/leaflet@1.9.4', $map);
        $this->assertStringContainsString('leaflet.markercluster@1.5.3', $map);
        $this->assertStringContainsString('leaflet.gridlayer.googlemutant', $map);

        $cdnDoc = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/ASSETS-CDN.md'));
        $this->assertStringContainsString('cdn.tailwindcss.com', $cdnDoc);
        $this->assertStringContainsString('8.2.32', $cdnDoc);
    }

    public function test_auth_strategy_and_single_session_gap_documented(): void
    {
        $middleware = (string) file_get_contents(app_path('Http/Middleware/EnforceSellerSingleSession.php'));
        $this->assertStringContainsString('hasSession()', $middleware);

        $sessionDoc = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/SESSION.md'));
        $this->assertStringContainsString('Bearer', $sessionDoc);
        $this->assertStringContainsString('session_version', $sessionDoc);
        $this->assertStringContainsString('session_replaced', $sessionDoc);

        $authDoc = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/AUTH.md'));
        $this->assertStringContainsString('Keychain', $authDoc);
        $this->assertStringContainsString('/api/mobile/v1/login', $authDoc);

        $this->assertFileDoesNotExist(config_path('cors.php'));
        $security = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/SECURITY.md'));
        $this->assertStringContainsString('config/cors.php', $security);
        $this->assertStringContainsString('capacitor://localhost', $security);
    }

    public function test_geolocation_adapter_and_map_plan(): void
    {
        $mapJs = (string) file_get_contents(public_path('js/operational-map.js'));
        $this->assertStringContainsString('navigator.geolocation', $mapJs);

        $gps = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/MAP-GPS.md'));
        $this->assertStringContainsString('LocationService', $gps);
        $this->assertStringContainsString('NSLocationWhenInUseUsageDescription', $gps);
        $this->assertStringContainsString('ACCESS_FINE_LOCATION', $gps);
        $this->assertStringContainsString('Sem rastrear vendedor em background', $gps);
    }

    public function test_offline_idempotency_and_secure_storage_plans(): void
    {
        $queue = (string) file_get_contents(public_path('js/field-offline-queue.js'));
        $this->assertStringContainsString('expandor.field.offline.queue.v1', $queue);

        $offline = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/OFFLINE.md'));
        $this->assertStringContainsString('não implementar', mb_strtolower($offline));

        $idem = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/IDEMPOTENCY.md'));
        $this->assertStringContainsString('Idempotency-Key', $idem);
        $this->assertStringContainsString('client_operation_id', $idem);

        $storage = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/LOCAL-STORAGE.md'));
        $this->assertStringContainsString('SQLite', $storage);
        $this->assertStringContainsString('Keychain', $storage);
    }

    public function test_push_deferred_and_deep_link_plan(): void
    {
        $matrix = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/APP-READINESS-MATRIX.md'));
        $this->assertStringContainsString('Push: DEFERRED', $matrix);

        $links = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/DEEP-LINKS.md'));
        $this->assertStringContainsString('redefinir-senha', $links);
        $this->assertStringContainsString('Não implementar nesta sprint', $links);
    }

    public function test_architecture_chooses_hybrid_not_remote_store_mvp(): void
    {
        $adr = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/ARCHITECTURE-DECISION.md'));
        $this->assertStringContainsString('Híbrido progressivo', $adr);
        $this->assertStringContainsString('Play/App Store', $adr);
        $pkg = (string) file_get_contents(base_path('package.json'));
        $this->assertStringNotContainsString('@capacitor', $pkg);
    }

    public function test_safe_area_keyboard_and_pwa_stub(): void
    {
        $css = (string) file_get_contents(public_path('css/client-ui.css'));
        $this->assertStringContainsString('safe-area-inset', $css);

        $operational = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $this->assertStringNotContainsString('viewport-fit=cover', $operational);

        $manifest = (string) file_get_contents(public_path('manifest.webmanifest'));
        $this->assertStringContainsString('"icons": []', $manifest);
        $this->assertFileDoesNotExist(public_path('sw.js'));
    }

    public function test_app_readiness_matrix_and_no_migrations(): void
    {
        $matrix = (string) file_get_contents(base_path('docs/sprint-8231-capacitor-app-readiness/APP-READINESS-MATRIX.md'));
        foreach (['Login', 'Map', 'GPS', 'Sale', 'Commission', 'Single session'] as $row) {
            $this->assertStringContainsString($row, $matrix);
        }
        $this->assertSame([], glob(base_path('database/migrations/*8231*')) ?: []);
    }
}
