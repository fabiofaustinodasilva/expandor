<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Enums\PropertyType;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class MapSavedMarkerDetailInteractionHotfixTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_renderer_uses_factory_and_map_click_hit_test_before_create(): void
    {
        $js = (string) file_get_contents(public_path('js/operational-map.js'));

        // Structural: the only marker add path must go through the shared factory.
        $this->assertSame(1, substr_count($js, 'function createSavedMarkerLayer'));
        $this->assertSame(1, substr_count($js, 'function bindSavedMarkerClick'));
        $this->assertSame(1, substr_count($js, 'function hitTestSavedMarkerFromMapEvent'));
        $this->assertSame(1, substr_count($js, 'clusterGroup.addLayer(layer)'));
        $this->assertStringContainsString('createSavedMarkerLayer(marker)', $js);
        $this->assertStringContainsString('bindSavedMarkerClick(layer, marker)', $js);

        // Map click must resolve saved pins before opening create flow.
        $hitPos = strpos($js, 'hitTestSavedMarkerFromMapEvent(event)');
        $createPos = strpos($js, 'openCreateAtMapTap(event.latlng)');
        $this->assertNotFalse($hitPos);
        $this->assertNotFalse($createPos);
        $this->assertLessThan($createPos, $hitPos);

        $this->assertStringContainsString("mapClickTrace('leaflet-marker-click'", $js);
        $this->assertStringContainsString("mapClickTrace('bindSavedMarkerClick'", $js);
        $this->assertStringContainsString("mapClickTrace('openPointDetails'", $js);
        $this->assertStringContainsString("mapClickTrace('fetch-point-detail'", $js);
        $this->assertStringContainsString("mapClickTrace('drawer-open'", $js);
        $this->assertStringContainsString('interactive: true', $js);
        $this->assertStringContainsString('hit-test-geometry', $js);
        $this->assertStringContainsString('__mapInspectProperty', $js);
    }

    public function test_marker_icon_css_keeps_pointer_events_and_pane_above_overlay(): void
    {
        $blade = (string) file_get_contents(resource_path('views/maps/index.blade.php'));

        $this->assertStringContainsString('.leaflet-pane.leaflet-marker-pane', $blade);
        $this->assertStringContainsString('z-index: 660 !important', $blade);
        $this->assertStringContainsString('.leaflet-marker-icon.map-house-pin-icon', $blade);
        $this->assertStringContainsString('pointer-events: auto !important', $blade);
        $this->assertStringContainsString('operational-map.js\') }}?v=59', $blade);
    }

    public function test_saved_property_detail_endpoint_returns_drawer_payload(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Marker Detail');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-marker-detail@map.test']);
        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id]);
        $address = Address::query()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'street' => 'Rua Detalhe',
            'number' => '10',
            'latitude' => -23.5505200,
            'longitude' => -46.6333080,
        ]);
        $property = Property::query()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'type' => PropertyType::HOUSE->value,
            'status' => PropertyStatus::INSTALLATION_REQUESTED->value,
            'latitude' => -23.5505200,
            'longitude' => -46.6333080,
            'created_by' => $seller->id,
        ]);

        $this->actingAs($seller)
            ->getJson(route('map.points.show', $property))
            ->assertOk()
            ->assertJsonPath('data.property_id', $property->id)
            ->assertJsonPath('data.status', PropertyStatus::INSTALLATION_REQUESTED->value);
    }
}
