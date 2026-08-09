<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Geo\Models\GeoMunicipality;
use App\Domains\Sales\Products\Models\Product;
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
 * Sprint 8.2.18 — Território inteligente para campanhas (cidade → todos/múltiplos setores).
 */
class Sprint8218SmartTerritoryCampaignTest extends TestCase
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

    public function test_campaign_with_all_sectors_keeps_empty_pivot(): void
    {
        [$admin, $city, $municipality] = $this->adminWithCity('Empresa 8218 All');

        $sector = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Centro',
        ]);

        $this->actingAs($admin)->post(route('campaigns.store'), [
            'name' => 'Campanha Cidade Inteira',
            'geo_municipality_id' => $municipality->id,
            'status' => CampaignStatus::DRAFT->value,
            'territory_mode' => 'all',
            'sector_ids' => [$sector->id],
            'goal_visits' => 10,
        ])->assertRedirect(route('campaigns.index'));

        $campaign = Campaign::query()->where('name', 'Campanha Cidade Inteira')->firstOrFail();
        $this->assertSame(0, $campaign->sectors()->count());
        $this->assertSame($city->id, $campaign->city_id);
    }

    public function test_campaign_with_one_sector(): void
    {
        [$admin, $city, $municipality] = $this->adminWithCity('Empresa 8218 One');

        $centro = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Centro',
        ]);
        Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Norte',
        ]);

        $this->actingAs($admin)->post(route('campaigns.store'), [
            'name' => 'Campanha Um Setor',
            'geo_municipality_id' => $municipality->id,
            'status' => CampaignStatus::DRAFT->value,
            'territory_mode' => 'sectors',
            'sector_ids' => [$centro->id],
        ])->assertRedirect(route('campaigns.index'));

        $campaign = Campaign::query()->where('name', 'Campanha Um Setor')->firstOrFail();
        $this->assertEqualsCanonicalizing([$centro->id], $campaign->sectors()->pluck('sectors.id')->all());
    }

    public function test_campaign_with_multiple_sectors(): void
    {
        [$admin, $city, $municipality] = $this->adminWithCity('Empresa 8218 Multi');

        $a = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Centro',
        ]);
        $b = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Vila Mutirão',
        ]);
        Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Sul',
        ]);

        $this->actingAs($admin)->post(route('campaigns.store'), [
            'name' => 'Campanha Multi',
            'geo_municipality_id' => $municipality->id,
            'status' => CampaignStatus::DRAFT->value,
            'territory_mode' => 'sectors',
            'sector_ids' => [$a->id, $b->id],
        ])->assertRedirect(route('campaigns.index'));

        $campaign = Campaign::query()->where('name', 'Campanha Multi')->firstOrFail();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $campaign->sectors()->pluck('sectors.id')->all());
    }

    public function test_sectors_for_city_endpoint_filters_by_city(): void
    {
        [$admin, $cityA] = $this->adminWithCity('Empresa 8218 Filter City');
        $cityB = City::factory()->create([
            'company_id' => $admin->company_id,
            'name' => 'Outra Cidade',
            'state' => 'GO',
        ]);

        $sectorA = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $cityA->id,
            'name' => 'Setor A',
        ]);
        Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $cityB->id,
            'name' => 'Setor B',
        ]);

        $response = $this->actingAs($admin)->getJson(
            route('campaigns.sectors-for-city', ['city_id' => $cityA->id])
        );

        $response->assertOk()->assertJsonPath('success', true);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$sectorA->id], $ids);
    }

    public function test_changing_city_rejects_incompatible_sectors(): void
    {
        [$admin, $cityA] = $this->adminWithCity('Empresa 8218 City Switch');

        $municipalityB = GeoMunicipality::query()->where('ibge_code', '5208707')->firstOrFail();
        $cityB = app(TerritoryService::class)->upsertCityFromCatalog($municipalityB);

        $sectorA = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $cityA->id,
            'name' => 'Só A',
        ]);
        Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $cityB->id,
            'name' => 'Só B',
        ]);

        $this->actingAs($admin)->post(route('campaigns.store'), [
            'name' => 'Campanha Setor Errado',
            'geo_municipality_id' => $municipalityB->id,
            'status' => CampaignStatus::DRAFT->value,
            'territory_mode' => 'sectors',
            'sector_ids' => [$sectorA->id],
        ])->assertSessionHasErrors('sector_ids');
    }

    public function test_edit_loads_existing_sector_selection(): void
    {
        [$admin, $city] = $this->adminWithCity('Empresa 8218 Edit Load');
        $sector = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Centro',
        ]);

        $campaign = Campaign::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Campanha Edit',
        ]);
        $campaign->sectors()->sync([$sector->id]);

        $this->actingAs($admin)
            ->get(route('campaigns.edit', $campaign))
            ->assertOk()
            ->assertSee('Áreas específicas')
            ->assertSee('Centro')
            ->assertSee('name="sector_ids[]"', false)
            ->assertSee('value="'.$sector->id.'"', false)
            ->assertSee('checked', false);
    }

    public function test_legacy_campaign_without_sectors_still_works_as_city_wide(): void
    {
        [$admin, $city] = $this->adminWithCity('Empresa 8218 Legacy');
        $sector = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Qualquer',
        ]);

        $campaign = Campaign::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Legado Sem Pivot',
        ]);

        $inCity = $this->propertyInSector($admin->company_id, $city->id, $sector->id, -15.1, -49.1);
        $otherCity = City::factory()->create([
            'company_id' => $admin->company_id,
            'name' => 'Outra',
            'state' => 'GO',
        ]);
        $otherSector = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $otherCity->id,
            'name' => 'Outro',
        ]);
        $outside = $this->propertyInSector($admin->company_id, $otherCity->id, $otherSector->id, -15.2, -49.2);

        Sanctum::actingAs($admin);
        $markers = collect(
            $this->getJson('/api/v1/maps/markers?campaign_id='.$campaign->id)->json('data.markers')
        )->pluck('property_id')->all();

        $this->assertContains($inCity->id, $markers);
        $this->assertNotContains($outside->id, $markers);
    }

    public function test_map_respects_city_wide_campaign(): void
    {
        [$admin, $city] = $this->adminWithCity('Empresa 8218 Map All');
        $s1 = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Norte',
        ]);
        $s2 = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Sul',
        ]);

        $campaign = Campaign::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
        ]);

        $p1 = $this->propertyInSector($admin->company_id, $city->id, $s1->id, -15.11, -49.11);
        $p2 = $this->propertyInSector($admin->company_id, $city->id, $s2->id, -15.12, -49.12);

        Sanctum::actingAs($admin);
        $ids = collect(
            $this->getJson('/api/v1/maps/markers?campaign_id='.$campaign->id)->json('data.markers')
        )->pluck('property_id')->all();

        $this->assertEqualsCanonicalizing([$p1->id, $p2->id], $ids);
    }

    public function test_map_respects_specific_sectors(): void
    {
        [$admin, $city] = $this->adminWithCity('Empresa 8218 Map Sectors');
        $s1 = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Centro',
        ]);
        $s2 = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Norte',
        ]);
        $s3 = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Sul',
        ]);

        $campaign = Campaign::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
        ]);
        $campaign->sectors()->sync([$s1->id, $s2->id]);

        $p1 = $this->propertyInSector($admin->company_id, $city->id, $s1->id, -15.21, -49.21);
        $p2 = $this->propertyInSector($admin->company_id, $city->id, $s2->id, -15.22, -49.22);
        $p3 = $this->propertyInSector($admin->company_id, $city->id, $s3->id, -15.23, -49.23);

        Sanctum::actingAs($admin);
        $ids = collect(
            $this->getJson('/api/v1/maps/markers?campaign_id='.$campaign->id)->json('data.markers')
        )->pluck('property_id')->all();

        $this->assertEqualsCanonicalizing([$p1->id, $p2->id], $ids);
        $this->assertNotContains($p3->id, $ids);
    }

    public function test_seller_sees_allowed_campaign_territory_on_map(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8218 Seller Map');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-seller-map@8218.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-map@8218.test']);

        app(TenantContext::class)->set($company, $admin);

        $city = City::factory()->create(['company_id' => $company->id, 'name' => 'Bom Jardim', 'state' => 'GO']);
        $s1 = Sector::factory()->create(['company_id' => $company->id, 'city_id' => $city->id, 'name' => 'Centro']);
        $s2 = Sector::factory()->create(['company_id' => $company->id, 'city_id' => $city->id, 'name' => 'Norte']);

        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->sectors()->sync([$s1->id]);
        $campaign->users()->sync([$seller->id]);

        $allowed = $this->propertyInSector($company->id, $city->id, $s1->id, -15.31, -49.31, $seller->id);
        $blocked = $this->propertyInSector($company->id, $city->id, $s2->id, -15.32, -49.32, $seller->id);

        Sanctum::actingAs($seller);
        $ids = collect(
            $this->getJson('/api/v1/maps/markers?campaign_id='.$campaign->id)->json('data.markers')
        )->pluck('property_id')->all();

        $this->assertContains($allowed->id, $ids);
        $this->assertNotContains($blocked->id, $ids);
    }

    public function test_seller_cannot_see_other_tenant_sector_via_endpoint(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 8218 A');
        $companyB = $this->makeCompanyWithPlan('Empresa 8218 B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'a@8218.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'b@8218.test']);

        app(TenantContext::class)->set($companyB, $adminB);
        $cityB = City::factory()->create(['company_id' => $companyB->id, 'name' => 'Cidade B', 'state' => 'GO']);
        Sector::factory()->create(['company_id' => $companyB->id, 'city_id' => $cityB->id, 'name' => 'Privado B']);

        app(TenantContext::class)->set($companyA, $adminA);

        $this->actingAs($adminA)
            ->getJson(route('campaigns.sectors-for-city', ['city_id' => $cityB->id]))
            ->assertNotFound();
    }

    public function test_admin_cannot_associate_cross_tenant_sector(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 8218 Assoc A');
        $companyB = $this->makeCompanyWithPlan('Empresa 8218 Assoc B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'assoc-a@8218.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'assoc-b@8218.test']);

        $municipality = GeoMunicipality::query()->where('ibge_code', '5203100')->firstOrFail();

        app(TenantContext::class)->set($companyA, $adminA);
        $cityA = app(TerritoryService::class)->upsertCityFromCatalog($municipality);

        app(TenantContext::class)->set($companyB, $adminB);
        $cityB = City::factory()->create(['company_id' => $companyB->id, 'name' => 'Cidade B', 'state' => 'GO']);
        $sectorB = Sector::factory()->create([
            'company_id' => $companyB->id,
            'city_id' => $cityB->id,
            'name' => 'Setor B',
        ]);

        app(TenantContext::class)->set($companyA, $adminA);

        $response = $this->actingAs($adminA)->post(route('campaigns.store'), [
            'name' => 'Tentativa Cross',
            'geo_municipality_id' => $municipality->id,
            'status' => CampaignStatus::DRAFT->value,
            'territory_mode' => 'sectors',
            'sector_ids' => [$sectorB->id],
        ]);

        $response->assertSessionHasErrors();
        $this->assertTrue(
            $response->getSession()->get('errors')->has('sector_ids')
            || $response->getSession()->get('errors')->has('sector_ids.0')
        );
        $this->assertSame(0, Campaign::query()->where('name', 'Tentativa Cross')->count());
    }

    public function test_custom_sector_works_on_campaign(): void
    {
        [$admin, $city, $municipality] = $this->adminWithCity('Empresa 8218 Custom');
        $custom = Sector::factory()->create([
            'company_id' => $admin->company_id,
            'city_id' => $city->id,
            'name' => 'Zona Rural Norte',
        ]);

        $this->actingAs($admin)->post(route('campaigns.store'), [
            'name' => 'Campanha Rural',
            'geo_municipality_id' => $municipality->id,
            'status' => CampaignStatus::DRAFT->value,
            'territory_mode' => 'sectors',
            'sector_ids' => [$custom->id],
        ])->assertRedirect(route('campaigns.index'));

        $campaign = Campaign::query()->where('name', 'Campanha Rural')->firstOrFail();
        $this->assertTrue($campaign->sectors()->where('sectors.id', $custom->id)->exists());
    }

    public function test_gps_first_approach_not_regressed(): void
    {
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa 8218 GPS');

        $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua GPS 8218',
            'latitude' => -15.45,
            'longitude' => -49.45,
            'status' => VisitStatus::NOT_HOME->value,
            'campaign_id' => $campaign->id,
        ])->assertCreated()
            ->assertJsonPath('data.status', PropertyStatus::NEW->value);
    }

    public function test_presentation_contract_not_regressed(): void
    {
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa 8218 Present');

        Product::factory()->create([
            'company_id' => $seller->company_id,
            'name' => 'Plano Fibra 8218',
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($seller)
            ->get(route('sales-app.products.present'))
            ->assertOk()
            ->assertSee('Plano Fibra 8218');
    }

    public function test_create_form_shows_territory_modes(): void
    {
        [$admin] = $this->adminWithCity('Empresa 8218 Form UX');

        $this->actingAs($admin)
            ->get(route('campaigns.create'))
            ->assertOk()
            ->assertSee('Toda a cidade')
            ->assertSee('Áreas específicas')
            ->assertSee('territory_mode', false);
    }

    /**
     * @return array{0: \App\Domains\Company\Models\User, 1: City, 2: GeoMunicipality}
     */
    protected function adminWithCity(string $companyName): array
    {
        $company = $this->makeCompanyWithPlan($companyName);
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-'.uniqid('', true).'@8218.test',
        ]);
        app(TenantContext::class)->set($company, $admin);

        $municipality = GeoMunicipality::query()->where('ibge_code', '5203100')->firstOrFail();
        $city = app(TerritoryService::class)->upsertCityFromCatalog($municipality);

        return [$admin, $city, $municipality];
    }

    /**
     * @return array{0: \App\Domains\Company\Models\User, 1: City, 2: Campaign}
     */
    protected function sellerWithCampaign(string $companyName): array
    {
        $company = $this->makeCompanyWithPlan($companyName);
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-'.uniqid('', true).'@8218.test',
        ]);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-'.uniqid('', true).'@8218.test',
        ]);
        app(TenantContext::class)->set($company, $admin);

        $city = City::factory()->create([
            'company_id' => $company->id,
            'name' => 'Cidade Seller',
            'state' => 'GO',
        ]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->sync([$seller->id]);

        return [$seller, $city, $campaign];
    }

    protected function propertyInSector(
        int $companyId,
        int $cityId,
        int $sectorId,
        float $lat,
        float $lng,
        ?int $createdBy = null,
    ): Property {
        return Property::factory()->create([
            'company_id' => $companyId,
            'created_by' => $createdBy,
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
