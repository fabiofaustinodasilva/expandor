<?php

namespace Tests\Feature\SalesApp;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SalesAppModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_sees_only_assigned_campaigns(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa SalesApp');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller@salesapp.test',
        ]);
        $otherSeller = $this->makeUser($company, Role::SELLER, [
            'email' => 'other@salesapp.test',
        ]);

        $city = City::factory()->create(['company_id' => $company->id]);

        $mine = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Campanha Do Vendedor',
            'status' => CampaignStatus::ACTIVE,
        ]);
        $mine->users()->attach($seller->id);

        $theirs = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Campanha De Outro Vendedor',
            'status' => CampaignStatus::ACTIVE,
        ]);
        $theirs->users()->attach($otherSeller->id);

        $response = $this->actingAs($seller)->get(route('sales-app.campaigns.index'));

        $response->assertOk()
            ->assertSee('Campanha Do Vendedor')
            ->assertDontSee('Campanha De Outro Vendedor');

        $this->actingAs($seller)
            ->get(route('sales-app.campaigns.properties', $theirs))
            ->assertNotFound();
    }

    public function test_seller_cannot_see_other_company_data(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Campo A');
        $companyB = $this->makeCompanyWithPlan('Empresa Campo B');

        $sellerA = $this->makeUser($companyA, Role::SELLER, [
            'email' => 'seller-a@salesapp.test',
        ]);
        $sellerB = $this->makeUser($companyB, Role::SELLER, [
            'email' => 'seller-b@salesapp.test',
        ]);

        $cityB = City::factory()->create(['company_id' => $companyB->id]);
        $campaignB = Campaign::factory()->create([
            'company_id' => $companyB->id,
            'city_id' => $cityB->id,
            'name' => 'Campanha Secreta Beta',
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaignB->users()->attach($sellerB->id);

        $this->actingAs($sellerA)
            ->get(route('sales-app.campaigns.index'))
            ->assertOk()
            ->assertDontSee('Campanha Secreta Beta');

        $this->actingAs($sellerA)
            ->get(route('sales-app.campaigns.properties', $campaignB))
            ->assertNotFound();
    }

    public function test_seller_can_register_visit(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Campo Visita');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-visit@salesapp.test',
        ]);

        app(TenantContext::class)->set($company, $seller);

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

        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
                'sector_id' => $sector->id,
            ])->id,
        ]);

        $response = $this->actingAs($seller)->post(
            route('sales-app.campaigns.visits.store', [$campaign, $property]),
            [
                'status' => VisitStatus::INTERESTED->value,
                'notes' => 'Abordagem rápida no campo',
                'schedule_follow_up' => 1,
                'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'follow_up_notes' => 'Levar proposta',
            ]
        );

        $response->assertRedirect(route('sales-app.campaigns.properties', $campaign));

        $this->assertDatabaseHas('visits', [
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::INTERESTED->value,
            'notes' => 'Abordagem rápida no campo',
        ]);

        $visit = Visit::query()->where('property_id', $property->id)->firstOrFail();

        $this->assertDatabaseHas('follow_ups', [
            'visit_id' => $visit->id,
            'user_id' => $seller->id,
            'notes' => 'Levar proposta',
        ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => VisitStatus::INTERESTED->value,
        ]);
    }
}
