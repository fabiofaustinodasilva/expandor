<?php

namespace Tests\Feature\Maps;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class MapSmartSearchTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_map_exposes_search_bar(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Search UI');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-search-ui@maps.test']);

        $this->actingAs($seller)
            ->get(route('map.index'))
            ->assertOk()
            ->assertSee('id="map-search"', false)
            ->assertSee('id="map-search-results"', false)
            ->assertSee('Buscar cliente, telefone, CPF ou endereço');
    }

    public function test_search_by_name(): void
    {
        [$seller, $mine] = $this->sellerWithOwnProperty('Empresa Search Nome', [
            'name' => 'Ana Clara Search',
            'phone' => '11911112222',
        ]);

        Sanctum::actingAs($seller);

        $hits = collect($this->getJson('/api/v1/maps/markers?q=Ana Clara')->json('data.markers'));
        $this->assertCount(1, $hits);
        $this->assertSame($mine->id, $hits->first()['property_id']);
        $this->assertSame('Ana Clara Search', $hits->first()['resident_name']);
    }

    public function test_search_by_phone(): void
    {
        [$seller, $mine] = $this->sellerWithOwnProperty('Empresa Search Tel', [
            'name' => 'Cliente Telefone',
            'phone' => '(11) 98888-7777',
        ]);

        Sanctum::actingAs($seller);

        $hits = collect($this->getJson('/api/v1/maps/markers?q=988887777')->json('data.markers'));
        $this->assertCount(1, $hits);
        $this->assertSame($mine->id, $hits->first()['property_id']);
    }

    public function test_search_by_address(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Search Addr');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-addr@maps.test']);
        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create([
            'company_id' => $company->id,
            'name' => 'Campinas Search',
        ]);
        $address = Address::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'street' => 'Rua das Palmeiras Search',
            'number' => '250',
            'neighborhood' => 'Jardim Europa',
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'created_by' => $seller->id,
            'latitude' => -22.9050000,
            'longitude' => -47.0600000,
            'status' => PropertyStatus::INTERESTED,
        ]);
        Resident::factory()->primary()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'Morador Endereço',
            'phone' => '19999990000',
        ]);

        Sanctum::actingAs($seller);

        $byStreet = collect($this->getJson('/api/v1/maps/markers?q=Palmeiras Search')->json('data.markers'));
        $this->assertCount(1, $byStreet);
        $this->assertSame($property->id, $byStreet->first()['property_id']);

        $byCity = collect($this->getJson('/api/v1/maps/markers?q=Campinas Search')->json('data.markers'));
        $this->assertCount(1, $byCity);
        $this->assertSame($property->id, $byCity->first()['property_id']);
    }

    public function test_seller_finds_only_own_clients(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Search Scope');
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'seller-a-scope@maps.test']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'seller-b-scope@maps.test']);
        app(TenantContext::class)->set($company, $sellerA);

        $mine = Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $sellerA->id,
            'latitude' => -23.5501000,
            'longitude' => -46.6301000,
        ]);
        Resident::factory()->primary()->create([
            'company_id' => $company->id,
            'property_id' => $mine->id,
            'name' => 'Cliente Do Seller A',
            'phone' => '11900001111',
        ]);

        $theirs = Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $sellerB->id,
            'latitude' => -23.5502000,
            'longitude' => -46.6302000,
        ]);
        Resident::factory()->primary()->create([
            'company_id' => $company->id,
            'property_id' => $theirs->id,
            'name' => 'Cliente Do Seller B',
            'phone' => '11900002222',
        ]);

        Sanctum::actingAs($sellerA);
        $hits = collect($this->getJson('/api/v1/maps/markers?q=Cliente Do Seller')->json('data.markers'));

        $this->assertCount(1, $hits);
        $this->assertSame($mine->id, $hits->first()['property_id']);
        $this->assertFalse($hits->contains(fn (array $m) => (int) $m['property_id'] === (int) $theirs->id));
    }

    public function test_manager_searches_whole_company(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Search Manager');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-search@maps.test']);
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'seller-mgr-a@maps.test']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'seller-mgr-b@maps.test']);
        app(TenantContext::class)->set($company, $manager);

        $a = Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $sellerA->id,
            'latitude' => -23.5510000,
            'longitude' => -46.6310000,
        ]);
        Resident::factory()->primary()->create([
            'company_id' => $company->id,
            'property_id' => $a->id,
            'name' => 'Cliente Empresa Alpha',
            'phone' => '11911110001',
        ]);

        $b = Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $sellerB->id,
            'latitude' => -23.5520000,
            'longitude' => -46.6320000,
        ]);
        Resident::factory()->primary()->create([
            'company_id' => $company->id,
            'property_id' => $b->id,
            'name' => 'Cliente Empresa Beta',
            'phone' => '11911110002',
        ]);

        Sanctum::actingAs($manager);
        $hits = collect($this->getJson('/api/v1/maps/markers?q=Cliente Empresa')->json('data.markers'));

        $this->assertCount(2, $hits);
        $ids = $hits->pluck('property_id')->all();
        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }

    public function test_search_is_tenant_isolated(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Search Isol A');
        $companyB = $this->makeCompanyWithPlan('Empresa Search Isol B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'admin-isol-a@maps.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'admin-isol-b@maps.test']);

        app(TenantContext::class)->set($companyA, $adminA);
        $propA = Property::factory()->create([
            'company_id' => $companyA->id,
            'created_by' => $adminA->id,
            'latitude' => -23.5530000,
            'longitude' => -46.6330000,
        ]);
        Resident::factory()->primary()->create([
            'company_id' => $companyA->id,
            'property_id' => $propA->id,
            'name' => 'Cliente Isolado UniqueX',
            'phone' => '11933334444',
        ]);

        app(TenantContext::class)->set($companyB, $adminB);
        $propB = Property::factory()->create([
            'company_id' => $companyB->id,
            'created_by' => $adminB->id,
            'latitude' => -22.9000000,
            'longitude' => -43.2000000,
        ]);
        Resident::factory()->primary()->create([
            'company_id' => $companyB->id,
            'property_id' => $propB->id,
            'name' => 'Cliente Isolado UniqueX',
            'phone' => '21933334444',
        ]);

        Sanctum::actingAs($adminA);
        $hits = collect($this->getJson('/api/v1/maps/markers?q=UniqueX')->json('data.markers'));
        $this->assertCount(1, $hits);
        $this->assertSame($propA->id, $hits->first()['property_id']);
    }

    public function test_search_hit_exposes_coordinates_for_map_focus_and_drawer_payload(): void
    {
        [$seller, $property] = $this->sellerWithOwnProperty('Empresa Search Focus', [
            'name' => 'Cliente Foco Drawer',
            'phone' => '11955556666',
            'whatsapp' => '11955556666',
        ], -23.5605000, -46.6405000);

        $campaign = Campaign::factory()->create(['company_id' => $seller->company_id]);
        Visit::factory()->create([
            'company_id' => $seller->company_id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::INTERESTED,
            'visited_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($seller);

        $marker = $this->getJson('/api/v1/maps/markers?q=Cliente Foco Drawer')
            ->assertOk()
            ->json('data.markers.0');

        $this->assertSame($property->id, $marker['property_id']);
        $this->assertEqualsWithDelta(-23.5605000, (float) $marker['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-46.6405000, (float) $marker['longitude'], 0.0001);

        $drawer = $this->actingAs($seller)
            ->getJson(route('map.points.show', $property))
            ->assertOk()
            ->json('data');

        $this->assertSame('Cliente Foco Drawer', $drawer['resident_name']);
        $this->assertSame('11955556666', $drawer['resident_phone']);
        $this->assertNotEmpty($drawer['last_visit_at']);
        $this->assertNotEmpty($drawer['last_visit_result']);
        $this->assertArrayHasKey('sold_product', $drawer);
        $this->assertArrayHasKey('next_action', $drawer);
        $this->assertEqualsWithDelta(-23.5605000, (float) $drawer['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-46.6405000, (float) $drawer['longitude'], 0.0001);
    }

    /**
     * @param  array{name: string, phone: string, whatsapp?: string}  $resident
     * @return array{0: \App\Domains\Company\Models\User, 1: Property}
     */
    protected function sellerWithOwnProperty(
        string $companyName,
        array $resident,
        float $lat = -23.5500000,
        float $lng = -46.6300000,
    ): array {
        $company = $this->makeCompanyWithPlan($companyName);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-'.uniqid().'@maps.search.test',
        ]);
        app(TenantContext::class)->set($company, $seller);

        $property = Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $seller->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'status' => PropertyStatus::INTERESTED,
        ]);

        Resident::factory()->primary()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => $resident['name'],
            'phone' => $resident['phone'],
            'whatsapp' => $resident['whatsapp'] ?? null,
        ]);

        return [$seller, $property];
    }
}
