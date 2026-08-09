<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Geo\Models\GeoMunicipality;
use App\Domains\Geo\Models\GeoState;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Sales\Territory\Services\TerritoryService;
use App\Domains\Visits\Enums\VisitStatus;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenantUsers;
use Tests\Support\SeedsGeoCatalog;
use Tests\TestCase;

/**
 * Sprint 8.2.18.1 — Catálogo territorial + UX campanha sem CRUD prévio.
 */
class Sprint82181TerritoryCatalogUxTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;
    use SeedsGeoCatalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seedMiniGeoCatalog();
    }

    public function test_catalog_has_states(): void
    {
        $this->assertTrue(GeoState::query()->where('uf', 'GO')->exists());
        $this->assertGreaterThanOrEqual(2, GeoState::query()->count());
    }

    public function test_municipality_belongs_to_state(): void
    {
        $mun = GeoMunicipality::query()->where('ibge_code', '5203100')->firstOrFail();
        $this->assertSame('GO', $mun->state->uf);
        $this->assertSame('Bom Jardim de Goiás', $mun->name);
    }

    public function test_company_can_create_campaign_without_prior_city(): void
    {
        [$admin, $municipality] = $this->adminWithMunicipality('Empresa 82181 Auto City');

        $this->assertSame(0, City::query()->count());

        $this->actingAs($admin)->post(route('campaigns.store'), [
            'name' => 'Campanha Sem CRUD',
            'geo_municipality_id' => $municipality->id,
            'territory_mode' => 'all',
            'status' => CampaignStatus::DRAFT->value,
        ])->assertRedirect(route('campaigns.index'));

        $campaign = Campaign::query()->where('name', 'Campanha Sem CRUD')->firstOrFail();
        $city = City::query()->findOrFail($campaign->city_id);

        $this->assertSame($admin->company_id, $city->company_id);
        $this->assertSame($municipality->id, $city->geo_municipality_id);
        $this->assertSame(0, $campaign->sectors()->count());
    }

    public function test_existing_city_is_reused_for_same_municipality(): void
    {
        [$admin, $municipality] = $this->adminWithMunicipality('Empresa 82181 Reuse');
        $city = app(TerritoryService::class)->upsertCityFromCatalog($municipality);

        $again = app(TerritoryService::class)->upsertCityFromCatalog($municipality);

        $this->assertSame($city->id, $again->id);
        $this->assertSame(1, City::query()->where('geo_municipality_id', $municipality->id)->count());
    }

    public function test_other_company_gets_own_operational_city(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 82181 A');
        $companyB = $this->makeCompanyWithPlan('Empresa 82181 B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'a@82181.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'b@82181.test']);
        [, $municipality] = $this->seedMiniGeoCatalog();

        app(TenantContext::class)->set($companyA, $adminA);
        $cityA = app(TerritoryService::class)->upsertCityFromCatalog($municipality);

        app(TenantContext::class)->set($companyB, $adminB);
        $cityB = app(TerritoryService::class)->upsertCityFromCatalog($municipality);

        $this->assertNotSame($cityA->id, $cityB->id);
        $this->assertSame($companyA->id, $cityA->company_id);
        $this->assertSame($companyB->id, $cityB->company_id);
        $this->assertSame($municipality->id, $cityA->geo_municipality_id);
        $this->assertSame($municipality->id, $cityB->geo_municipality_id);
    }

    public function test_whole_city_keeps_empty_pivot(): void
    {
        [$admin, $municipality] = $this->adminWithMunicipality('Empresa 82181 Whole');
        $city = app(TerritoryService::class)->upsertCityFromCatalog($municipality);
        Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Centro',
        ]);

        $this->actingAs($admin)->post(route('campaigns.store'), [
            'name' => 'Toda Cidade',
            'geo_municipality_id' => $municipality->id,
            'territory_mode' => 'all',
            'sector_ids' => [],
            'status' => CampaignStatus::DRAFT->value,
        ])->assertRedirect();

        $campaign = Campaign::query()->where('name', 'Toda Cidade')->firstOrFail();
        $this->assertSame(0, $campaign->sectors()->count());
    }

    public function test_inline_area_creation(): void
    {
        [$admin, $municipality] = $this->adminWithMunicipality('Empresa 82181 Inline');

        $response = $this->actingAs($admin)->postJson(route('campaigns.areas.store'), [
            'geo_municipality_id' => $municipality->id,
            'name' => 'Zona Rural Norte',
            'description' => 'Fazendas',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.area.name', 'Zona Rural Norte');

        $cityId = (int) $response->json('data.city_id');
        $sectorId = (int) $response->json('data.area.id');

        $this->assertDatabaseHas('sectors', [
            'id' => $sectorId,
            'city_id' => $cityId,
            'company_id' => $admin->company_id,
            'name' => 'Zona Rural Norte',
        ]);
    }

    public function test_reserved_todos_name_rejected_and_ignored_in_ux_listing(): void
    {
        [$admin, $municipality] = $this->adminWithMunicipality('Empresa 82181 Todos');
        $city = app(TerritoryService::class)->upsertCityFromCatalog($municipality);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TerritoryService::class)->upsertSector([
            'city_id' => $city->id,
            'name' => 'Todos os setores',
        ]);
    }

    public function test_legacy_todos_sector_not_treated_as_picker_option(): void
    {
        [$admin, $municipality] = $this->adminWithMunicipality('Empresa 82181 Legacy Todos');
        $city = app(TerritoryService::class)->upsertCityFromCatalog($municipality);

        Sector::query()->withoutGlobalScopes()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'TODOS',
            'active' => true,
        ]);
        $real = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Centro',
        ]);

        $areas = $this->actingAs($admin)
            ->getJson(route('campaigns.areas-for-municipality', [
                'geo_municipality_id' => $municipality->id,
            ]))
            ->assertOk()
            ->json('data.areas');

        $names = collect($areas)->pluck('name')->all();
        $this->assertContains('Centro', $names);
        $this->assertNotContains('TODOS', $names);
        $this->assertSame($real->id, collect($areas)->firstWhere('name', 'Centro')['id']);
    }

    public function test_cross_tenant_blocked_on_area_listing_via_city_scope(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 82181 Cross A');
        $companyB = $this->makeCompanyWithPlan('Empresa 82181 Cross B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'cross-a@82181.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'cross-b@82181.test']);
        [, $municipality] = $this->seedMiniGeoCatalog();

        app(TenantContext::class)->set($companyB, $adminB);
        $cityB = app(TerritoryService::class)->upsertCityFromCatalog($municipality);
        Sector::factory()->create([
            'company_id' => $companyB->id,
            'city_id' => $cityB->id,
            'name' => 'Privado B',
        ]);

        app(TenantContext::class)->set($companyA, $adminA);
        $areas = $this->actingAs($adminA)
            ->getJson(route('campaigns.areas-for-municipality', [
                'geo_municipality_id' => $municipality->id,
            ]))
            ->assertOk()
            ->json('data.areas');

        $this->assertSame([], $areas);
        $cityA = City::query()->where('geo_municipality_id', $municipality->id)->firstOrFail();
        $this->assertSame($companyA->id, $cityA->company_id);
        $this->assertNotSame($cityB->id, $cityA->id);
    }

    public function test_municipalities_filtered_by_uf(): void
    {
        [$admin] = $this->adminWithMunicipality('Empresa 82181 UF Filter');

        $go = $this->actingAs($admin)
            ->getJson(route('campaigns.municipalities', ['uf' => 'GO', 'q' => 'Bom']))
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($go);
        $this->assertTrue(collect($go)->every(fn ($m) => str_contains($m['name'], 'Bom') || str_contains($m['name'], 'Goi')));

        $sp = $this->actingAs($admin)
            ->getJson(route('campaigns.municipalities', ['uf' => 'SP', 'q' => 'Bom Jardim']))
            ->assertOk()
            ->json('data');

        $this->assertSame([], $sp);
    }

    public function test_edit_legacy_campaign_without_geo_link_works(): void
    {
        [$admin] = $this->adminWithMunicipality('Empresa 82181 Legacy Edit');
        $city = City::factory()->create([
            'company_id' => $admin->company_id,
            'name' => 'Cidade Legada',
            'state' => 'GO',
            'geo_municipality_id' => null,
        ]);
        $campaign = Campaign::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Legada',
        ]);

        $this->actingAs($admin)
            ->get(route('campaigns.edit', $campaign))
            ->assertOk()
            ->assertSee('Toda a cidade')
            ->assertSee('Cidade Legada');

        $this->actingAs($admin)->put(route('campaigns.update', $campaign), [
            'name' => 'Legada Atualizada',
            'city_id' => $city->id,
            'territory_mode' => 'all',
        ])->assertRedirect(route('campaigns.index'));

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Legada Atualizada',
            'city_id' => $city->id,
        ]);
    }

    public function test_map_still_respects_whole_city_and_areas(): void
    {
        [$admin, $municipality] = $this->adminWithMunicipality('Empresa 82181 Map');
        $city = app(TerritoryService::class)->upsertCityFromCatalog($municipality);
        $s1 = Sector::factory()->create(['company_id' => $admin->company_id, 'city_id' => $city->id, 'name' => 'Centro']);
        $s2 = Sector::factory()->create(['company_id' => $admin->company_id, 'city_id' => $city->id, 'name' => 'Norte']);

        $campaign = Campaign::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
        ]);
        $campaign->sectors()->sync([$s1->id]);

        $p1 = $this->propertyInSector($admin->company_id, $city->id, $s1->id, -15.1, -49.1);
        $p2 = $this->propertyInSector($admin->company_id, $city->id, $s2->id, -15.2, -49.2);

        Sanctum::actingAs($admin);
        $ids = collect(
            $this->getJson('/api/v1/maps/markers?campaign_id='.$campaign->id)->json('data.markers')
        )->pluck('property_id')->all();

        $this->assertSame([$p1->id], $ids);
        $this->assertNotContains($p2->id, $ids);
    }

    public function test_seller_first_approach_still_works(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82181 Seller');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-s@82181.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller@82181.test']);
        app(TenantContext::class)->set($company, $admin);
        [, $municipality] = $this->seedMiniGeoCatalog();
        $city = app(TerritoryService::class)->upsertCityFromCatalog($municipality);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->sync([$seller->id]);

        $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua 82181',
            'latitude' => -15.4,
            'longitude' => -49.4,
            'status' => VisitStatus::NOT_HOME->value,
            'campaign_id' => $campaign->id,
        ])->assertCreated()
            ->assertJsonPath('data.status', PropertyStatus::NEW->value);
    }

    public function test_create_form_shows_catalog_ux(): void
    {
        [$admin] = $this->adminWithMunicipality('Empresa 82181 Form');

        $this->actingAs($admin)
            ->get(route('campaigns.create'))
            ->assertOk()
            ->assertSee('Território')
            ->assertSee('Toda a cidade')
            ->assertSee('Áreas específicas')
            ->assertSee('Criar área')
            ->assertSee('Goiás');
    }

    /**
     * @return array{0: \App\Domains\Company\Models\User, 1: GeoMunicipality}
     */
    protected function adminWithMunicipality(string $companyName): array
    {
        $company = $this->makeCompanyWithPlan($companyName);
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-'.uniqid('', true).'@82181.test',
        ]);
        app(TenantContext::class)->set($company, $admin);
        [, $municipality] = $this->seedMiniGeoCatalog();

        return [$admin, $municipality];
    }

    protected function propertyInSector(
        int $companyId,
        int $cityId,
        int $sectorId,
        float $lat,
        float $lng,
    ): Property {
        return Property::factory()->create([
            'company_id' => $companyId,
            'latitude' => $lat,
            'longitude' => $lng,
            'status' => PropertyStatus::NEW,
            'address_id' => Address::factory()->create([
                'company_id' => $companyId,
                'city_id' => $cityId,
                'sector_id' => $sectorId,
            ])->id,
        ]);
    }
}
