<?php

namespace Tests\Feature\Campaigns;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class CampaignsModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_a_cannot_access_campaigns_from_company_b(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Campanhas A');
        $companyB = $this->makeCompanyWithPlan('Empresa Campanhas B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@campaigns.test',
        ]);

        $cityA = City::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Cidade Alpha Camp',
        ]);

        $cityB = City::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Cidade Beta Camp',
        ]);

        Campaign::factory()->create([
            'company_id' => $companyA->id,
            'city_id' => $cityA->id,
            'name' => 'Campanha Alpha Unica',
        ]);

        Campaign::factory()->create([
            'company_id' => $companyB->id,
            'city_id' => $cityB->id,
            'name' => 'Campanha Beta Unica',
        ]);

        $response = $this->actingAs($adminA)->get(route('campaigns.index'));

        $response->assertOk()
            ->assertSee('Campanha Alpha Unica')
            ->assertDontSee('Campanha Beta Unica');

        $foreign = Campaign::withoutGlobalScopes()
            ->where('company_id', $companyB->id)
            ->firstOrFail();

        $this->actingAs($adminA)
            ->get(route('campaigns.edit', $foreign))
            ->assertNotFound();
    }

    public function test_only_authorized_users_can_manage_campaigns(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Auth Campanhas');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@campaigns.test',
        ]);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller@campaigns.test',
        ]);
        $manager = $this->makeUser($company, Role::MANAGER, [
            'email' => 'manager@campaigns.test',
        ]);

        $this->actingAs($viewer)->get(route('campaigns.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('campaigns.create'))->assertForbidden();

        $this->actingAs($seller)->get(route('campaigns.index'))->assertOk();
        $this->actingAs($seller)->get(route('campaigns.create'))->assertForbidden();

        $this->actingAs($manager)->get(route('campaigns.create'))->assertOk();
    }

    public function test_campaign_associates_sellers_correctly(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Assoc Campanhas');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-assoc@campaigns.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $city = City::factory()->create([
            'company_id' => $company->id,
        ]);

        $sectorA = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Setor Norte',
        ]);

        $sectorB = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Setor Sul',
        ]);

        $sellerA = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-a@campaigns.test',
            'name' => 'Vendedor Alpha',
        ]);

        $sellerB = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-b@campaigns.test',
            'name' => 'Vendedor Beta',
        ]);

        $response = $this->actingAs($admin)->post(route('campaigns.store'), [
            'name' => 'Porta a Porta Centro',
            'description' => 'Operação externa',
            'city_id' => $city->id,
            'status' => CampaignStatus::DRAFT->value,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'goal_visits' => 80,
            'user_ids' => [$sellerA->id, $sellerB->id],
            'sector_ids' => [$sectorA->id, $sectorB->id],
        ]);

        $response->assertRedirect(route('campaigns.index'));

        $campaign = Campaign::query()->where('name', 'Porta a Porta Centro')->firstOrFail();

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'company_id' => $company->id,
            'city_id' => $city->id,
            'goal_visits' => 80,
            'created_by' => $admin->id,
            'status' => CampaignStatus::DRAFT->value,
        ]);

        $this->assertTrue($campaign->users()->where('users.id', $sellerA->id)->exists());
        $this->assertTrue($campaign->users()->where('users.id', $sellerB->id)->exists());
        $this->assertSame(2, $campaign->users()->count());
        $this->assertSame(2, $campaign->sectors()->count());

        $this->actingAs($admin)
            ->post(route('campaigns.activate', $campaign))
            ->assertRedirect(route('campaigns.index'));

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => CampaignStatus::ACTIVE->value,
        ]);
    }
}
