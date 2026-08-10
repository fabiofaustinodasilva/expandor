<?php

namespace Tests\Feature\Integrations;

use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Integrations\Enums\CompanyIntegrationStatus;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Services\MapFrontendConfigBuilder;
use App\Domains\Integrations\Services\MapIntegrationResolver;
use App\Domains\Integrations\Services\MapProviderRuntimeReporter;
use App\Domains\Integrations\Support\IntegrationProviders;
use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint8222GoogleMapsProviderTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    private const KEY_A = 'AIzaSyTenantAKeyValue1234567890AAAA';

    private const KEY_B = 'AIzaSyTenantBKeyValue1234567890BBBB';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_01_basic_free_plan_resolves_leaflet(): void
    {
        $company = $this->makeCompanyWithPlan('Basic Co', 'free');
        $decision = app(MapIntegrationResolver::class)->resolve($company);
        $config = app(MapFrontendConfigBuilder::class)->forCompany($company);

        $this->assertTrue($decision->usesDefaultLeaflet());
        $this->assertSame(IntegrationProviders::LEAFLET_OSM, $config->provider);
        $this->assertFalse($config->usesGoogleVisual());
    }

    public function test_02_pro_without_config_resolves_leaflet(): void
    {
        $company = $this->makeCompanyWithPlan('Pro NoCfg', 'professional');
        $config = app(MapFrontendConfigBuilder::class)->forCompany($company);

        $this->assertSame(IntegrationProviders::LEAFLET_OSM, $config->provider);
        $this->assertSame('not_configured', $config->reason);
        $this->assertNull($config->browserKey());
    }

    public function test_03_pro_config_error_resolves_leaflet(): void
    {
        $company = $this->makeCompanyWithPlan('Pro Err', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        CompanyIntegration::query()->create([
            'company_id' => $company->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Error,
            'credentials' => ['browser_api_key' => self::KEY_A],
        ]);

        $config = app(MapFrontendConfigBuilder::class)->forCompany($company);
        $this->assertSame(IntegrationProviders::LEAFLET_OSM, $config->provider);
        $this->assertFalse($config->usesGoogleVisual());
    }

    public function test_04_pro_connected_returns_google_maps_config(): void
    {
        $company = $this->connectedPro('Pro OK');
        $config = app(MapFrontendConfigBuilder::class)->forCompany($company);

        $this->assertSame(IntegrationProviders::GOOGLE_MAPS, $config->provider);
        $this->assertSame(IntegrationProviders::LEAFLET_OSM, $config->fallback);
        $this->assertTrue($config->usesGoogleVisual());
        $this->assertSame(self::KEY_A, $config->browserKey());
        $this->assertSame(IntegrationProviders::GOOGLE_MAPS, app(MapIntegrationResolver::class)->resolve($company)->visualProvider());
    }

    public function test_05_seller_inherits_company_provider(): void
    {
        $company = $this->connectedPro('Pro Seller Inherit');
        $seller = $this->makeUser($company, Role::SELLER);
        app(TenantContext::class)->set($company, $seller);

        $response = $this->actingAs($seller)->get(route('map.index'));
        $response->assertOk();
        $response->assertSee('data-map-provider="google_maps"', false);
        $response->assertSee('maps.googleapis.com/maps/api/js', false);
        $response->assertSee(self::KEY_A, false);
    }

    public function test_06_cross_tenant_key_does_not_leak(): void
    {
        $companyA = $this->connectedPro('Tenant A', self::KEY_A);
        $companyB = $this->makeCompanyWithPlan('Tenant B', 'professional');
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($companyB, $adminB);

        CompanyIntegration::query()->create([
            'company_id' => $companyB->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Connected,
            'credentials' => ['browser_api_key' => self::KEY_B],
        ]);

        $configB = app(MapFrontendConfigBuilder::class)->forCompany($companyB);
        $this->assertSame(self::KEY_B, $configB->browserKey());
        $this->assertNotSame(self::KEY_A, $configB->browserKey());

        $htmlB = $this->actingAs($adminB)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringContainsString(self::KEY_B, $htmlB);
        $this->assertStringNotContainsString(self::KEY_A, $htmlB);

        unset($companyA);
    }

    public function test_07_downgrade_forces_leaflet_without_deleting_credentials(): void
    {
        $company = $this->connectedPro('Downgrade Co');
        $resolver = app(MapIntegrationResolver::class);
        $this->assertTrue($resolver->resolve($company)->usesGoogleMaps());

        $free = Plan::query()->where('slug', 'free')->firstOrFail();
        Subscription::query()->where('company_id', $company->id)->update(['plan_id' => $free->id]);
        $resolver->forget($company);

        $config = app(MapFrontendConfigBuilder::class)->forCompany($company->fresh());
        $this->assertSame(IntegrationProviders::LEAFLET_OSM, $config->provider);
        $this->assertNull($config->browserKey());

        $integration = CompanyIntegration::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $this->assertTrue($integration->hasBrowserApiKey());
    }

    public function test_08_disconnect_forces_leaflet(): void
    {
        $company = $this->connectedPro('Disconnect Co');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $this->actingAs($admin)
            ->post(route('operations.integrations.google-maps.disconnect'))
            ->assertRedirect();

        $config = app(MapFrontendConfigBuilder::class)->forCompany($company->fresh());
        $this->assertSame(IntegrationProviders::LEAFLET_OSM, $config->provider);
        $this->assertNull($config->browserKey());

        $html = $this->actingAs($admin)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString(self::KEY_A, $html);
        $this->assertStringNotContainsString('maps.googleapis.com/maps/api/js', $html);
    }

    public function test_09_html_basic_does_not_contain_google_key_or_script(): void
    {
        $company = $this->makeCompanyWithPlan('Basic HTML', 'free');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $html = $this->actingAs($admin)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringContainsString('data-map-provider="leaflet_osm"', $html);
        $this->assertStringNotContainsString('maps.googleapis.com/maps/api/js', $html);
        $this->assertStringNotContainsString('AIza', $html);
        $this->assertStringNotContainsString('GoogleMutant', $html);
    }

    public function test_10_html_other_tenant_does_not_contain_foreign_key(): void
    {
        $this->connectedPro('Owner of A', self::KEY_A);

        $companyB = $this->makeCompanyWithPlan('Other Tenant', 'free');
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($companyB, $adminB);

        $html = $this->actingAs($adminB)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString(self::KEY_A, $html);
    }

    public function test_11_google_config_only_for_authorized_tenant(): void
    {
        $entitled = $this->connectedPro('Authorized', self::KEY_A);
        $config = app(MapFrontendConfigBuilder::class)->forCompany($entitled);
        $this->assertTrue($config->usesGoogleVisual());

        $notEntitled = $this->makeCompanyWithPlan('Not Entitled', 'free');
        CompanyIntegration::query()->withoutGlobalScopes()->create([
            'company_id' => $notEntitled->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Connected,
            'credentials' => ['browser_api_key' => self::KEY_B],
        ]);

        $denied = app(MapFrontendConfigBuilder::class)->forCompany($notEntitled);
        $this->assertFalse($denied->usesGoogleVisual());
        $this->assertNull($denied->browserKey());
    }

    public function test_12_fallback_js_exists(): void
    {
        $providerJs = file_get_contents(public_path('js/map-provider.js'));
        $opsJs = file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString('createGoogleMapsProvider', $providerJs);
        $this->assertStringContainsString('googleMutant', $providerJs);
        $this->assertStringContainsString('activateLeafletFallback', $opsJs);
        $this->assertStringContainsString('Mapa padrão ativado temporariamente.', $opsJs);
        $this->assertStringContainsString('waitForGoogleMaps', $providerJs);
        $this->assertStringContainsString('gm_authFailure', $opsJs);
        $this->assertStringContainsString('keepLeafletAndWarn', $opsJs);
        $this->assertStringContainsString('BOOT ORDER (required)', $opsJs);
    }

    public function test_map_has_explicit_max_zoom_before_marker_cluster_initialization(): void
    {
        $opsJs = file_get_contents(public_path('js/operational-map.js'));

        $mapPos = strpos($opsJs, "L.map('operational-map'");
        $this->assertNotFalse($mapPos);
        $mapBlock = substr($opsJs, $mapPos, 450);
        $this->assertStringContainsString('maxZoom: 19', $mapBlock, 'L.map options must include explicit maxZoom: 19');

        $this->assertMatchesRegularExpression(
            '/BOOT ORDER \(required\):[\s\S]*?attachLeafletProvider\(\);[\s\S]*?L\.markerClusterGroup\(\{[\s\S]*?map\.addLayer\(clusterGroup\)/',
            $opsJs,
            'Boot order must be: attachLeafletProvider → markerClusterGroup → addLayer(cluster)'
        );

        $this->assertStringContainsString('disableClusteringAtZoom: 18', $opsJs);
        $this->assertStringContainsString('spiderfyOnMaxZoom: true', $opsJs);

        $blade = file_get_contents(resource_path('views/maps/index.blade.php'));
        $this->assertStringContainsString("js/operational-map.js') }}?v=57", $blade);
        $this->assertStringContainsString("js/map-provider.js') }}?v=5", $blade);
    }

    public function test_13_fallback_avoids_double_init_guard(): void
    {
        $opsJs = file_get_contents(public_path('js/operational-map.js'));
        $this->assertStringContainsString('basemapInitToken', $opsJs);
        $this->assertStringContainsString('destroyCurrentProvider', $opsJs);

        $controller = file_get_contents(app_path('Http/Controllers/Web/Maps/MapController.php'));
        $this->assertStringContainsString('force_google_failure', $controller);
        $this->assertStringContainsString("environment(['local', 'testing'])", $controller);
    }

    public function test_14_manager_map_preserves_surface_and_loads_google_script_when_connected(): void
    {
        $company = $this->connectedPro('Manager Map');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $html = $this->actingAs($admin)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringContainsString('id="map-search"', $html);
        $this->assertStringContainsString('id="btn-map-filters"', $html);
        $this->assertStringContainsString('commercial-legend', $html);
        $this->assertStringContainsString('basemap-street', $html);
        $this->assertStringContainsString('basemap-satellite', $html);
        $this->assertStringContainsString('maps.googleapis.com/maps/api/js', $html);
        $this->assertStringContainsString('Leaflet.GoogleMutant.js', $html);
        $this->assertStringContainsString('map-provider.js', $html);
        $this->assertStringContainsString('operational-map.js', $html);
    }

    public function test_15_seller_map_click_surface_preserved(): void
    {
        $company = $this->connectedPro('Seller Click');
        $seller = $this->makeUser($company, Role::SELLER);
        app(TenantContext::class)->set($company, $seller);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringContainsString('data-map-provider="google_maps"', $html);
        $this->assertStringContainsString('id="point-modal"', $html);
        $this->assertStringContainsString('map-today-chip', $html);

        $opsJs = file_get_contents(public_path('js/operational-map.js'));
        $this->assertMatchesRegularExpression('/map\.on\([\'"]click[\'"]/', $opsJs);
    }

    public function test_16_gps_helper_preserved(): void
    {
        $opsJs = file_get_contents(public_path('js/operational-map.js'));
        $this->assertStringContainsString('geolocation', $opsJs);
        $blade = file_get_contents(resource_path('views/maps/index.blade.php'));
        $this->assertStringContainsString('Minha localização', $blade);
    }

    public function test_17_filters_preserved_in_blade(): void
    {
        $blade = file_get_contents(resource_path('views/maps/index.blade.php'));
        foreach (['filter-city', 'filter-sector', 'filter-campaign', 'filter-seller', 'filter-status'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $blade);
        }
    }

    public function test_18_campaign_territory_hooks_preserved(): void
    {
        $opsJs = file_get_contents(public_path('js/operational-map.js'));
        $this->assertStringContainsString('regionSelectMode', $opsJs);
        $this->assertStringContainsString('openRegionCampaignModal', $opsJs);
        $blade = file_get_contents(resource_path('views/maps/index.blade.php'));
        $this->assertStringContainsString('Selecionar área', $blade);
    }

    public function test_19_street_satellite_per_provider(): void
    {
        $providerJs = file_get_contents(public_path('js/map-provider.js'));
        $this->assertStringContainsString("type: 'roadmap'", $providerJs);
        $this->assertStringContainsString("type: 'satellite'", $providerJs);
        $this->assertStringContainsString('tile.openstreetmap.org', $providerJs);
        $this->assertStringContainsString('arcgisonline.com', $providerJs);
        $this->assertStringContainsString('setBasemap', $providerJs);
    }

    public function test_20_attribution_preserved_in_providers(): void
    {
        $providerJs = file_get_contents(public_path('js/map-provider.js'));
        $this->assertStringContainsString('OpenStreetMap', $providerJs);
        $this->assertStringContainsString('Esri', $providerJs);
        $this->assertStringContainsString('Google branding/attribution must remain visible', $providerJs);
    }

    public function test_21_runtime_reporter_does_not_log_key_and_throttles(): void
    {
        Log::spy();
        Cache::flush();

        $company = $this->connectedPro('Log Co');
        $reporter = app(MapProviderRuntimeReporter::class);

        $reporter->report($company, 'timeout', 'failed with '.self::KEY_A);
        $reporter->report($company, 'timeout', 'failed again '.self::KEY_A);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($company): bool {
                $encoded = json_encode($context) ?: '';

                return $message === 'map.provider.runtime_fallback'
                    && ! str_contains($encoded, self::KEY_A)
                    && ($context['code'] ?? '') === 'timeout'
                    && ($context['company_id'] ?? null) === $company->id;
            });
    }

    public function test_22_mercado_pago_intact(): void
    {
        PaymentGatewaySetting::query()->create([
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADOPAGO,
            'mode' => PaymentGatewaySetting::MODE_SANDBOX,
            'public_key' => 'TEST-PK-8222',
            'access_token' => 'TEST-AT-8222',
            'active' => true,
        ]);

        $platform = $this->makePlatformAdmin();
        $this->actingAs($platform)
            ->get(route('platform.integrations.index'))
            ->assertOk()
            ->assertSee('Mercado Pago')
            ->assertSee('Platform-managed')
            ->assertDontSee(self::KEY_A);

        $this->assertDatabaseHas('payment_gateway_settings', [
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADOPAGO,
            'public_key' => 'TEST-PK-8222',
        ]);
    }

    public function test_force_google_failure_only_in_local_testing_and_fallback_route_exists(): void
    {
        $company = $this->connectedPro('Force Fail');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $this->actingAs($admin)
            ->get(route('map.index', ['force_google_failure' => 1]))
            ->assertOk()
            ->assertSee('data-map-force-google-failure="1"', false);

        $this->actingAs($admin)
            ->post(route('map.provider-fallback'), [
                'code' => 'forced_failure',
                'message' => 'qa',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_provider_fallback_requires_auth(): void
    {
        $this->post(route('map.provider-fallback'), [
            'code' => 'x',
            'message' => 'y',
        ])->assertRedirect();
    }

    private function connectedPro(string $name, string $key = self::KEY_A): \App\Domains\Company\Models\Company
    {
        $company = $this->makeCompanyWithPlan($name, 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        CompanyIntegration::query()->create([
            'company_id' => $company->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Connected,
            'credentials' => ['browser_api_key' => $key],
        ]);

        app(MapIntegrationResolver::class)->forget($company);

        return $company;
    }
}
