<?php

namespace Tests\Feature\Mobile;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_mobile_authentication(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Mobile Auth');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller@mobile-auth.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/mobile/v1/login', [
            'email' => 'seller@mobile-auth.test',
            'password' => 'password',
            'device_id' => '11111111-1111-4111-8111-111111111111',
            'device_name' => 'Android',
            'platform' => 'android',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'seller@mobile-auth.test')
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'company_id', 'permissions']]]);

        Sanctum::actingAs($seller);

        $this->getJson('/api/mobile/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'seller@mobile-auth.test')
            ->assertJsonPath('data.company.id', $company->id);
    }

    public function test_tenant_isolation_on_mobile_campaigns(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Mobile A');
        $companyB = $this->makeCompanyWithPlan('Empresa Mobile B');

        $sellerA = $this->makeUser($companyA, Role::SELLER, [
            'email' => 'seller-a@mobile.test',
        ]);
        $sellerB = $this->makeUser($companyB, Role::SELLER, [
            'email' => 'seller-b@mobile.test',
        ]);

        $cityB = City::factory()->create(['company_id' => $companyB->id]);
        $campaignB = Campaign::factory()->create([
            'company_id' => $companyB->id,
            'city_id' => $cityB->id,
            'name' => 'Campanha Secreta Mobile B',
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaignB->users()->attach($sellerB->id);

        Sanctum::actingAs($sellerA);

        $this->getJson('/api/mobile/v1/campaigns')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Campanha Secreta Mobile B']);

        $this->getJson("/api/mobile/v1/campaign/{$campaignB->id}/properties")
            ->assertNotFound();
    }

    public function test_seller_cannot_access_another_sellers_campaign(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Mobile Sellers');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller1@mobile.test',
        ]);
        $other = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller2@mobile.test',
        ]);

        $city = City::factory()->create(['company_id' => $company->id]);

        $mine = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Minha Campanha Mobile',
            'status' => CampaignStatus::ACTIVE,
        ]);
        $mine->users()->attach($seller->id);

        $theirs = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Campanha Do Colega',
            'status' => CampaignStatus::ACTIVE,
        ]);
        $theirs->users()->attach($other->id);

        Sanctum::actingAs($seller);

        $this->getJson('/api/mobile/v1/campaigns')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Minha Campanha Mobile'])
            ->assertJsonMissing(['name' => 'Campanha Do Colega']);

        $this->getJson("/api/mobile/v1/campaign/{$theirs->id}/properties")
            ->assertNotFound();
    }

    public function test_register_visit_via_mobile_api(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Mobile Visita');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-visit@mobile.test',
        ]);

        $city = City::factory()->create(['company_id' => $company->id]);
        $sector = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);

        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->attach($seller->id);
        $campaign->sectors()->attach($sector->id);

        $address = Address::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'sector_id' => $sector->id,
        ]);

        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'latitude' => -16.6869,
            'longitude' => -49.2648,
        ]);

        Sanctum::actingAs($seller);

        $this->postJson('/api/mobile/v1/visits', [
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'status' => VisitStatus::INTERESTED->value,
            'notes' => 'Visita mobile',
            'latitude' => -16.6869,
            'longitude' => -49.2648,
            'client_uuid' => '11111111-1111-1111-1111-111111111111',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', VisitStatus::INTERESTED->value)
            ->assertJsonPath('data.client_uuid', '11111111-1111-1111-1111-111111111111');

        $this->assertDatabaseHas('visits', [
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::INTERESTED->value,
            'notes' => 'Visita mobile',
        ]);

        $this->assertSame(1, Visit::query()->where('user_id', $seller->id)->count());

        $this->getJson('/api/mobile/v1/pending-sync')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['server_time', 'sync_token', 'pending_from_server']]);
    }
}
