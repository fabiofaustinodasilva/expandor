<?php

namespace Tests\Feature\Maps;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Maps\Enums\MapMarkerColor;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Territory\Models\City;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.29 — house pins, clusters polish, legend parity (visual only).
 */
class Sprint8229MapVisualPolishTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_map_scripts_cache_bust_and_house_pin_assets(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8229 Scripts');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-8229@test']);

        $html = $this->actingAs($admin)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('operational-map.js?v=60', $html);
        $this->assertStringContainsString('map-provider.js?v=6', $html);
        $this->assertStringContainsString('map-house-pin', $html);
        $this->assertStringContainsString('map-legend-pin', $html);
        $this->assertStringNotContainsString('operational-map.js?v=57', $html);
    }

    public function test_status_colors_unchanged_source_of_truth(): void
    {
        $this->assertSame('#22c55e', MapMarkerColor::forStatus(PropertyStatus::CUSTOMER)->value);
        $this->assertSame('#22c55e', MapMarkerColor::forStatus(PropertyStatus::INSTALLATION_REQUESTED)->value);
        $this->assertSame('#3b82f6', MapMarkerColor::forStatus(PropertyStatus::INTERESTED)->value);
        $this->assertSame('#f97316', MapMarkerColor::forStatus(PropertyStatus::RETURN_LATER)->value);
        $this->assertSame('#64748b', MapMarkerColor::forStatus(PropertyStatus::NO_INTEREST)->value);
        $this->assertSame('#ef4444', MapMarkerColor::forStatus(PropertyStatus::NEW)->value);

        $this->assertSame('R', MapMarkerColor::markForStatus(PropertyStatus::RETURN_LATER));
        $this->assertSame('×', MapMarkerColor::markForStatus(PropertyStatus::NO_INTEREST));
    }

    public function test_commercial_layer_exposes_house_pin_renderer(): void
    {
        $js = file_get_contents(public_path('js/map-provider.js'));
        $this->assertNotFalse($js);
        $this->assertStringContainsString('pinHtml(', $js);
        $this->assertStringContainsString('legendPinHtml(', $js);
        $this->assertStringContainsString('map-house-pin', $js);
        $this->assertStringContainsString('map-house-pin-house', $js);
        $this->assertStringContainsString('is-draft', $js);
        $this->assertStringContainsString('clusterIcon(', $js);
    }

    public function test_operational_map_uses_house_pin_not_generic_circle_for_properties(): void
    {
        $js = file_get_contents(public_path('js/operational-map.js'));
        $this->assertNotFalse($js);
        $this->assertStringContainsString('map-house-pin-icon', $js);
        $this->assertStringContainsString('pinHtml', $js);
        $this->assertStringContainsString('iconSize: [28, 36]', $js);
        $this->assertStringContainsString('iconAnchor: [14, 34]', $js);
        // Property icons must not use the old 18×18 circle DivIcon factory shape.
        $this->assertStringNotContainsString('map-marker-dot" style="background:', $js);
        // GPS remains circleMarker (seller), not house pin.
        $this->assertStringContainsString('L.circleMarker', $js);
        $this->assertStringContainsString("fillColor: '#38bdf8'", $js);
        // Temporary create-draft is ghost/neutral, not return orange.
        $this->assertStringContainsString("mode === 'create-draft' ? '#94a3b8'", $js);
        $this->assertStringContainsString('draft: true', $js);
        // Selected highlight still applied without swapping status color.
        $this->assertStringContainsString('map-marker-selected', $js);
    }

    public function test_legend_renders_house_pins_with_official_labels_and_colors(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8229 Legend');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-8229@test']);

        $html = $this->actingAs($manager)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('map-legend-pin', $html);
        $this->assertStringContainsString('map-house-pin-body', $html);
        $this->assertStringContainsString('Cliente / instalação', $html);
        $this->assertStringContainsString('Interessado', $html);
        $this->assertStringContainsString('Retorno', $html);
        $this->assertStringContainsString('Sem interesse', $html);
        $this->assertStringContainsString('Novo', $html);
        $this->assertStringContainsString('#22c55e', $html);
        $this->assertStringContainsString('#3b82f6', $html);
        $this->assertStringContainsString('#f97316', $html);
        $this->assertStringContainsString('#64748b', $html);
        $this->assertStringContainsString('#ef4444', $html);
    }

    public function test_seller_and_manager_share_same_marker_system(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8229 Roles');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'm2-8229@test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 's2-8229@test']);
        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->attach($seller->id);

        app(TenantContext::class)->set($company, $manager);
        $managerHtml = $this->actingAs($manager)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringContainsString('operational-map.js?v=60', $managerHtml);
        $this->assertStringContainsString('map-house-pin', $managerHtml);

        app(TenantContext::class)->set($company, $seller);
        $sellerHtml = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringContainsString('operational-map.js?v=60', $sellerHtml);
        $this->assertStringContainsString('map-house-pin', $sellerHtml);
        $this->assertStringContainsString('Minha localização', $sellerHtml);
    }

    public function test_cluster_and_basemap_hooks_remain(): void
    {
        $jsMap = file_get_contents(public_path('js/operational-map.js'));
        $jsProv = file_get_contents(public_path('js/map-provider.js'));
        $this->assertStringContainsString('markerClusterGroup', $jsMap);
        $this->assertStringContainsString('clusterIcon', $jsMap);
        $this->assertStringContainsString('ExpandorMapProvider', $jsProv);
        $this->assertStringContainsString('google_maps', $jsProv);
        $this->assertStringContainsString('leaflet_osm', $jsProv);
    }

    public function test_house_pin_markup_stays_compact(): void
    {
        $js = file_get_contents(public_path('js/map-provider.js'));
        $this->assertLessThan(
            1200,
            strlen((string) preg_replace('/\s+/', ' ', $this->extractPinHtmlSnippet($js))),
            'pinHtml should stay a compact reusable SVG snippet'
        );
    }

    protected function extractPinHtmlSnippet(string $js): string
    {
        if (! preg_match('/pinHtml\([^)]*\)\s*\{([\s\S]*?)\n\s*legendPinHtml/', $js, $m)) {
            return $js;
        }

        return $m[1];
    }
}
