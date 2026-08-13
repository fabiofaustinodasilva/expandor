<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Enums\PropertyType;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class MapPointCreateRepositionHotfixTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_web_map_click_create_and_marker_handlers_are_wired(): void
    {
        $js = (string) file_get_contents(public_path('js/operational-map.js'));
        $blade = (string) file_get_contents(resource_path('views/maps/index.blade.php'));

        $this->assertStringContainsString("map.on('click'", $js);
        $this->assertStringContainsString('openCreateAtMapTap', $js);
        $this->assertStringContainsString("mapDebug('mapClick'", $js);
        $this->assertStringContainsString("mapDebug('openCreatePoint'", $js);
        $this->assertStringContainsString("mapDebug('markerClick'", $js);
        $this->assertStringContainsString('L.DomEvent.stopPropagation', $js);
        $this->assertStringContainsString('openMapModal', $js);
        $this->assertStringContainsString('startAdjustMode', $js);
        $this->assertStringContainsString('saveAdjustedPosition', $js);
        $this->assertStringContainsString('pointAdjustTemplate', $js);
        $this->assertStringContainsString('display: flex !important', $blade);
        $this->assertStringContainsString("@section('content')", $blade);
    }

    public function test_mobile_adjust_location_contract_is_wired(): void
    {
        $api = (string) file_get_contents(resource_path('js/mobile/mobile-api.js'));
        $adapter = (string) file_get_contents(resource_path('js/mobile/map-adapter.js'));
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $routes = (string) file_get_contents(base_path('routes/api.php'));
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringContainsString('adjustPointLocation', $api);
        $this->assertStringContainsString('/points/${id}/location', $api);
        $this->assertStringContainsString("method: 'PUT'", $api);

        $this->assertStringContainsString('beginAdjust', $adapter);
        $this->assertStringContainsString('setAdjustPreview', $adapter);
        $this->assertStringContainsString('cancelAdjust', $adapter);
        $this->assertStringContainsString('entry.marker.setLatLng', $adapter);
        $this->assertStringContainsString('L.DomEvent.stopPropagation', $adapter);

        $this->assertStringContainsString('beginExistingPositionAdjust', $shell);
        $this->assertStringContainsString('confirmAdjustPosition', $shell);
        $this->assertStringContainsString('beginCreatePositionPick', $shell);
        $this->assertStringContainsString('point-adjust-map', $shell);
        $this->assertStringContainsString('id="point-adjust-map"', $prepare);
        $this->assertStringContainsString('id="adjust-sheet"', $prepare);

        $this->assertStringContainsString("Route::put('/points/{point}/location'", $routes);
        $this->assertStringContainsString('adjustLocation', $routes);
    }

    public function test_web_adjust_endpoint_persists_coordinates_and_blocks_cross_tenant(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Ajuste A');
        $sellerA = $this->makeUser($companyA, Role::SELLER, ['email' => 'seller-adjust-a@map.test']);
        $companyB = $this->makeCompanyWithPlan('Empresa Ajuste B');
        $sellerB = $this->makeUser($companyB, Role::SELLER, ['email' => 'seller-adjust-b@map.test']);

        $city = City::query()->create([
            'company_id' => $companyA->id,
            'name' => 'Cidade A',
            'state' => 'SP',
            'is_active' => true,
        ]);
        $address = Address::query()->create([
            'company_id' => $companyA->id,
            'city_id' => $city->id,
            'street' => 'Rua A',
            'number' => '10',
            'latitude' => -23.5000000,
            'longitude' => -46.6000000,
        ]);
        $property = Property::query()->create([
            'company_id' => $companyA->id,
            'address_id' => $address->id,
            'type' => PropertyType::HOUSE->value,
            'status' => PropertyStatus::INTERESTED->value,
            'latitude' => -23.5000000,
            'longitude' => -46.6000000,
            'created_by' => $sellerA->id,
        ]);

        app(TenantContext::class)->set($companyA, $sellerA);

        $this->actingAs($sellerA)
            ->putJson(route('map.points.adjust', $property), [
                'latitude' => -23.5100000,
                'longitude' => -46.6100000,
            ])
            ->assertOk()
            ->assertJsonPath('data.property_id', $property->id);

        $property->refresh();
        $this->assertEqualsWithDelta(-23.5100000, (float) $property->latitude, 0.0001);
        $this->assertEqualsWithDelta(-46.6100000, (float) $property->longitude, 0.0001);

        app(TenantContext::class)->set($companyB, $sellerB);

        $this->actingAs($sellerB)
            ->putJson(route('map.points.adjust', $property), [
                'latitude' => -23.5200000,
                'longitude' => -46.6200000,
            ])
            ->assertNotFound();
    }

    public function test_mobile_adjust_location_endpoint_persists_and_keeps_property_id(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Mobile Ajuste');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-mobile-adjust@map.test',
            'password' => 'password',
        ]);
        $city = City::query()->create([
            'company_id' => $company->id,
            'name' => 'Cidade M',
            'state' => 'RJ',
            'is_active' => true,
        ]);
        $address = Address::query()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'street' => 'Rua Mobile',
            'number' => '22',
            'latitude' => -22.9000000,
            'longitude' => -43.2000000,
        ]);
        $property = Property::query()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'type' => PropertyType::HOUSE->value,
            'status' => PropertyStatus::INSTALLATION_REQUESTED->value,
            'latitude' => -22.9000000,
            'longitude' => -43.2000000,
            'created_by' => $seller->id,
        ]);

        $device = '22222222-2222-4222-8222-000000000901';
        $token = $this->postJson('/api/mobile/v1/login', [
            'email' => $seller->email,
            'password' => 'password',
            'device_id' => $device,
            'device_name' => 'Android',
            'platform' => 'android',
            'app_version' => '8.2.34',
        ])->assertOk()->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Device-Id' => $device,
            'Accept' => 'application/json',
        ])->putJson('/api/mobile/v1/points/'.$property->id.'/location', [
            'latitude' => -22.9100000,
            'longitude' => -43.2100000,
            'company_id' => 999999,
        ])
            ->assertOk()
            ->assertJsonPath('data.property_id', $property->id)
            ->assertJsonPath('data.latitude', -22.91)
            ->assertJsonPath('data.longitude', -43.21);

        $property->refresh();
        $this->assertSame($company->id, (int) $property->company_id);
        $this->assertEqualsWithDelta(-22.9100000, (float) $property->latitude, 0.0001);
        $this->assertEqualsWithDelta(-43.2100000, (float) $property->longitude, 0.0001);
        $this->assertSame(PropertyStatus::INSTALLATION_REQUESTED, $property->status);
    }
}
