<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Support\ClientArea\ClientNav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.32.1 — restore seller visual after local Lucide/Tailwind migration.
 */
class Sprint8232SellerVisualRegressionHotfixTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_bundle_stays_local_without_tailwind_cdn(): void
    {
        $operational = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $js = (string) file_get_contents(resource_path('js/seller-app.js'));
        $partial = (string) file_get_contents(resource_path('views/layouts/partials/seller-vendor.blade.php'));

        $this->assertStringNotContainsString('cdn.tailwindcss.com', $operational);
        $this->assertStringNotContainsString('unpkg.com/lucide', $operational);
        $this->assertStringContainsString('layouts.partials.seller-vendor', $operational);
        $this->assertStringContainsString('vendor/expandor/seller-app.css', $partial);
        $this->assertStringContainsString('filemtime', $partial);
        $this->assertStringContainsString('createIcons({', $js);
        $this->assertStringContainsString('icons', $js);
        $this->assertStringContainsString("from 'lucide'", $js);
        $this->assertStringContainsString("from 'leaflet'", $js);
    }

    public function test_sidebar_classes_icons_and_active_state_are_preserved(): void
    {
        $rail = (string) file_get_contents(resource_path('views/layouts/partials/client-rail.blade.php'));
        $css = (string) file_get_contents(public_path('css/client-ui.css'));
        $layout = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));

        $this->assertStringContainsString('class="w-5 h-5"', $rail);
        $this->assertStringContainsString("? 'active' : ''", $rail);
        $this->assertStringContainsString('.op-rail a.active', $css);
        $this->assertStringContainsString('.op-rail a:hover', $css);
        $this->assertStringContainsString('width: 1.25rem', $css);
        $this->assertStringContainsString('stroke-width: 2', $css);
        $this->assertStringContainsString('width: 52px', $layout);
        $this->assertStringContainsString('border-radius: 14px', $layout);
        $this->assertStringContainsString('grid-template-columns: 72px', $layout);
        $this->assertStringContainsString('--client-rail-w: 72px', $css);
        $this->assertStringContainsString('--client-bp-md: 900px', $css);
    }

    public function test_seller_rail_icons_and_map_actions_exist(): void
    {
        $nav = (string) file_get_contents(app_path('Support/ClientArea/ClientNav.php'));
        $map = (string) file_get_contents(resource_path('views/maps/index.blade.php'));

        foreach (['map-pinned', 'calendar-clock', 'contact', 'bar-chart-3', 'wallet', 'package'] as $icon) {
            $this->assertStringContainsString("'icon' => '{$icon}'", $nav, $icon);
        }

        $this->assertStringContainsString('id="btn-present-products"', $map);
        $this->assertStringContainsString('id="btn-recenter-location"', $map);
        $this->assertStringContainsString('id="basemap-street"', $map);
        $this->assertStringContainsString('id="basemap-satellite"', $map);
        $this->assertStringContainsString('map-btn-present', $map);
        $this->assertStringContainsString('map-btn-recenter', $map);
        $this->assertStringContainsString('id="map-today-chip"', $map);
    }

    public function test_seller_and_manager_map_pages_keep_surface(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8232.1 Visual');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-82321@test']);
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-82321@test']);

        $sellerHtml = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringContainsString('op-rail', $sellerHtml);
        $this->assertStringContainsString('data-lucide="map-pinned"', $sellerHtml);
        $this->assertStringContainsString('class="w-5 h-5"', $sellerHtml);
        $this->assertStringContainsString('btn-present-products', $sellerHtml);
        $this->assertStringContainsString('btn-recenter-location', $sellerHtml);
        $this->assertStringContainsString('basemap-street', $sellerHtml);
        $this->assertStringContainsString('seller-app.css?v=', $sellerHtml);
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $sellerHtml);
        $this->assertStringNotContainsString('unpkg.com/lucide', $sellerHtml);

        $labels = array_column(ClientNav::railItems($seller), 'label');
        foreach (['Mapa', 'Agenda', 'Clientes', 'Resultado', 'Comissão'] as $label) {
            $this->assertContains($label, $labels);
        }

        $managerHtml = $this->actingAs($admin)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringContainsString('id="operational-map"', $managerHtml);
        $this->assertStringContainsString('btn-map-filters', $managerHtml);
        $this->assertStringContainsString('basemap-street', $managerHtml);
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $managerHtml);
    }

    public function test_built_css_contains_icon_and_map_utilities(): void
    {
        $built = public_path('vendor/expandor/seller-app.css');
        if (! is_file($built)) {
            $this->markTestSkipped('seller-app.css not built in this environment');
        }

        $css = (string) file_get_contents($built);
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $css);
        $this->assertStringNotContainsString('unpkg.com', $css);
        $this->assertStringContainsString('.w-5', $css);
        $this->assertStringContainsString('.h-5', $css);
        $this->assertStringContainsString('.flex-1', $css);
        $this->assertStringContainsString('.rounded-2xl', $css);
        $this->assertStringContainsString('.h-12', $css);
    }
}
