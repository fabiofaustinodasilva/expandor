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

    public function test_persisted_pins_mount_on_feature_group_not_cluster_by_default(): void
    {
        $js = (string) file_get_contents(public_path('js/operational-map.js'));
        $host = (string) file_get_contents(public_path('js/map-saved-property-layer.js'));
        $blade = (string) file_get_contents(resource_path('views/maps/index.blade.php'));

        $this->assertStringContainsString('L.featureGroup().addTo(map)', $js);
        $this->assertStringContainsString('mountSavedPropertyLayer(layer)', $js);
        $this->assertStringContainsString('savedPropertyHost.host.addLayer(layer)', $js);
        $this->assertStringDoesNotContain($js, 'clusterGroup.addLayer(layer)');
        $this->assertStringDoesNotContain($js, 'hitTestSavedMarkerFromMapEvent');
        $this->assertStringContainsString('debug_no_cluster', $host);
        $this->assertStringContainsString('debug_cluster', $host);
        $this->assertStringContainsString('window.__mapDebugProperty', $js);
        $this->assertStringContainsString('window.__mapOpenProperty', $js);
        $this->assertStringContainsString("[MapRuntime]", $js);
        $this->assertStringContainsString('map-saved-property-layer.js', $blade);
        $this->assertStringContainsString('operational-map.js\') }}?v=61', $blade);
    }

    public function test_detail_endpoint_works_for_finalized_and_open_statuses(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Marker Statuses');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-marker-statuses@map.test']);
        app(TenantContext::class)->set($company, $seller);
        $city = City::factory()->create(['company_id' => $company->id]);

        $statuses = [
            PropertyStatus::NEW,
            PropertyStatus::INTERESTED,
            PropertyStatus::RETURN_LATER,
            PropertyStatus::INSTALLATION_REQUESTED,
            PropertyStatus::NO_INTEREST,
            PropertyStatus::CUSTOMER,
        ];

        foreach ($statuses as $index => $status) {
            $address = Address::query()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
                'street' => 'Rua Status '.$index,
                'number' => (string) ($index + 1),
                'latitude' => -23.5505200 + ($index * 0.001),
                'longitude' => -46.6333080,
            ]);
            $property = Property::query()->create([
                'company_id' => $company->id,
                'address_id' => $address->id,
                'type' => PropertyType::HOUSE->value,
                'status' => $status->value,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'created_by' => $seller->id,
            ]);

            $this->actingAs($seller)
                ->getJson(route('map.points.show', $property))
                ->assertOk()
                ->assertJsonPath('data.property_id', $property->id)
                ->assertJsonPath('data.status', $status->value)
                ->assertJsonPath('data.can_adjust', true);
        }
    }

    public function test_adjust_then_show_keeps_same_property_id(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Marker Adjust Chain');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-marker-adjust-chain@map.test']);
        app(TenantContext::class)->set($company, $seller);
        $city = City::factory()->create(['company_id' => $company->id]);
        $address = Address::query()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'street' => 'Rua Ajuste Cadeia',
            'number' => '7',
            'latitude' => -23.5500000,
            'longitude' => -46.6300000,
        ]);
        $property = Property::query()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'type' => PropertyType::HOUSE->value,
            'status' => PropertyStatus::INSTALLATION_REQUESTED->value,
            'latitude' => -23.5500000,
            'longitude' => -46.6300000,
            'created_by' => $seller->id,
        ]);

        $this->actingAs($seller)
            ->putJson(route('map.points.adjust', $property), [
                'latitude' => -23.5511111,
                'longitude' => -46.6311111,
            ])
            ->assertOk()
            ->assertJsonPath('data.property_id', $property->id);

        $this->actingAs($seller)
            ->getJson(route('map.points.show', $property))
            ->assertOk()
            ->assertJsonPath('data.property_id', $property->id)
            ->assertJsonPath('data.can_adjust', true);

        $property->refresh();
        $this->assertEqualsWithDelta(-23.5511111, (float) $property->latitude, 0.0001);
        $this->assertEqualsWithDelta(-46.6311111, (float) $property->longitude, 0.0001);
    }

    private function assertStringDoesNotContain(string $haystack, string $needle): void
    {
        $this->assertFalse(
            str_contains($haystack, $needle),
            "Did not expect to find [{$needle}]"
        );
    }
}
