<?php

namespace Tests\Feature\Maps;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Maps\Enums\MapMarkerColor;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\VisitStatus;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * After create/sale the pin is drawn from the 2xx payload; pan/zoom rebuilds
 * layers from GET /api/v1/maps/markers?campaign_id&bbox. New points must
 * survive that reload without a frontend upsert.
 */
class MapMarkerPersistenceAfterBboxReloadTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_first_approach_without_sector_survives_campaign_bbox_reload(): void
    {
        [$seller, $campaign, $city, $sector, $old] = $this->seedCampaignWithOldPin();

        $this->assertSame($sector->id, (int) $old->address->sector_id, 'old pin has sector');

        foreach ([
            VisitStatus::INTERESTED,
            VisitStatus::RETURN_LATER,
            VisitStatus::NO_INTEREST,
            VisitStatus::NOT_HOME,
        ] as $status) {
            $payload = [
                'city_id' => $city->id,
                'street' => 'Posição no mapa',
                'latitude' => -16.7581000,
                'longitude' => -51.2001000,
                'status' => $status->value,
                'campaign_id' => $campaign->id,
            ];
            if ($status === VisitStatus::RETURN_LATER) {
                $payload['follow_up_at'] = now()->addDay()->format('Y-m-d H:i:s');
            }

            $created = $this->actingAs($seller)
                ->postJson(route('map.first-approach'), $payload)
                ->assertCreated();

            $newId = (int) $created->json('data.property_id');
            $property = Property::query()->findOrFail($newId);
            $address = $property->address()->firstOrFail();

            $this->assertSame((int) $city->id, (int) $address->city_id);
            $this->assertNull($address->sector_id);
            $this->assertEqualsWithDelta(-16.7581, (float) $property->latitude, 0.0001);
            $this->assertEqualsWithDelta(-51.2001, (float) $property->longitude, 0.0001);

            $ids = $this->markerIds($seller, $campaign, -16.76, -16.75, -51.21, -51.19);
            $this->assertContains($old->id, $ids, 'old pin must remain');
            $this->assertContains($newId, $ids, 'new pin status '.$status->value.' must remain after bbox+campaign reload');
        }
    }

    public function test_sale_first_approach_survives_campaign_bbox_reload_and_wrong_request_city(): void
    {
        [$seller, $campaign, $city, $sector, $old] = $this->seedCampaignWithOldPin();
        $otherCity = City::factory()->create([
            'company_id' => $seller->company_id,
            'name' => 'Outra Cidade',
        ]);
        $product = Product::factory()->create([
            'company_id' => $seller->company_id,
            'name' => '300 Mega',
            'price' => 89.90,
            'commission_amount' => 40,
        ]);

        $created = $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $otherCity->id,
            'street' => 'Posição no mapa',
            'latitude' => -16.7582000,
            'longitude' => -51.2002000,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'campaign_id' => $campaign->id,
            'customer_name' => 'Cliente Campo',
            'customer_phone' => '64988887777',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'due_day' => 10,
        ])->assertCreated();

        $newId = (int) $created->json('data.property_id');
        $property = Property::query()->findOrFail($newId);
        $address = $property->address()->firstOrFail();

        $this->assertSame((int) $city->id, (int) $address->city_id, 'campaign city is persisted, not dropdown city');
        $this->assertNull($address->sector_id);
        $this->assertSame(PropertyStatus::INSTALLATION_REQUESTED, $property->status);

        $markers = $this->markers($seller, $campaign, -16.76, -16.75, -51.21, -51.19);
        $ids = $markers->pluck('property_id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($old->id, $ids);
        $this->assertContains($newId, $ids);

        $hit = $markers->first(fn ($m) => (int) $m['property_id'] === $newId);
        $this->assertSame('installation_requested', $hit['status']);
        $this->assertSame(MapMarkerColor::GREEN->value, $hit['color']);
        $this->assertSame('customer', $hit['commercial_group']);
    }

    public function test_null_sector_in_campaign_city_is_included_even_without_new_visit_row_match_on_legacy_pin(): void
    {
        [$seller, $campaign] = $this->seedCampaignWithOldPin();
        $legacy = $this->propertyAt($seller, $campaign, null, -16.7583, -51.2003, PropertyStatus::INTERESTED);

        $ids = $this->markerIds($seller, $campaign, -16.76, -16.75, -51.21, -51.19);
        $this->assertContains($legacy->id, $ids);
    }

    /**
     * @return array{0: mixed, 1: Campaign, 2: City, 3: Sector, 4: Property}
     */
    private function seedCampaignWithOldPin(): array
    {
        $company = $this->makeCompanyWithPlan('Empresa Marker Persist');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-persist@map.test']);
        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id, 'name' => 'Bom Jardim de Goiás']);
        $sector = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Centro',
        ]);
        Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Setor Norte',
        ]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->sync([$seller->id]);
        $campaign->sectors()->sync([$sector->id]);

        $old = $this->propertyAt($seller, $campaign, $sector->id, -16.7570000, -51.1990000, PropertyStatus::INTERESTED);

        return [$seller, $campaign, $city, $sector, $old];
    }

    private function propertyAt($seller, Campaign $campaign, ?int $sectorId, float $lat, float $lng, PropertyStatus $status): Property
    {
        $address = Address::factory()->create([
            'company_id' => $seller->company_id,
            'city_id' => $campaign->city_id,
            'sector_id' => $sectorId,
            'street' => 'Rua Antiga',
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        return Property::factory()->create([
            'company_id' => $seller->company_id,
            'address_id' => $address->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'status' => $status,
            'created_by' => $seller->id,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function markers($seller, Campaign $campaign, float $minLat, float $maxLat, float $minLng, float $maxLng)
    {
        $query = http_build_query([
            'campaign_id' => $campaign->id,
            'min_latitude' => $minLat,
            'max_latitude' => $maxLat,
            'min_longitude' => $minLng,
            'max_longitude' => $maxLng,
        ]);

        return collect(
            $this->actingAs($seller)
                ->getJson('/api/v1/maps/markers?'.$query)
                ->assertOk()
                ->json('data.markers')
        );
    }

    /**
     * @return list<int>
     */
    private function markerIds($seller, Campaign $campaign, float $minLat, float $maxLat, float $minLng, float $maxLng): array
    {
        return $this->markers($seller, $campaign, $minLat, $maxLat, $minLng, $maxLng)
            ->pluck('property_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
