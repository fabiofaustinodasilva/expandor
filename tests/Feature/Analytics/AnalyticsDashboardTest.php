<?php

namespace Tests\Feature\Analytics;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_a_does_not_see_metrics_from_company_b(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Analytics A');
        $companyB = $this->makeCompanyWithPlan('Empresa Analytics B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@analytics.test',
        ]);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, [
            'email' => 'admin-b@analytics.test',
        ]);

        $cityA = City::factory()->create(['company_id' => $companyA->id]);
        $cityB = City::factory()->create(['company_id' => $companyB->id]);

        $campaignA = Campaign::factory()->create([
            'company_id' => $companyA->id,
            'city_id' => $cityA->id,
        ]);
        $campaignB = Campaign::factory()->create([
            'company_id' => $companyB->id,
            'city_id' => $cityB->id,
        ]);

        $propertyA = Property::factory()->create(['company_id' => $companyA->id]);
        $propertyB = Property::factory()->create(['company_id' => $companyB->id]);

        Visit::factory()->create([
            'company_id' => $companyA->id,
            'campaign_id' => $campaignA->id,
            'property_id' => $propertyA->id,
            'user_id' => $adminA->id,
            'status' => VisitStatus::INTERESTED,
        ]);

        Visit::factory()->count(3)->create([
            'company_id' => $companyB->id,
            'campaign_id' => $campaignB->id,
            'property_id' => $propertyB->id,
            'user_id' => $adminB->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED,
        ]);

        $response = $this->actingAs($adminA)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Empresa Analytics A')
            ->assertDontSee('Empresa Analytics B')
            ->assertSee('Resultados')
            ->assertSee('Hoje')
            ->assertSee('Ranking de vendedores')
            ->assertSee('Funil comercial')
            ->assertSee('1')
            ->assertViewHas('metrics', function ($metrics) {
                return $metrics->visits_total === 1
                    && $metrics->interested_total === 1
                    && $metrics->installations_total === 0
                    && $metrics->properties_total >= 1
                    && $metrics->team_view === true
                    && isset($metrics->funnel['points']);
            });
    }

    public function test_calculations_respect_filters(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Filtros Analytics');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-filters@analytics.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $cityA = City::factory()->create([
            'company_id' => $company->id,
            'name' => 'Cidade Filtro A',
        ]);
        $cityB = City::factory()->create([
            'company_id' => $company->id,
            'name' => 'Cidade Filtro B',
        ]);

        $sectorA = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $cityA->id,
        ]);
        $sectorB = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $cityB->id,
        ]);

        $campaignA = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $cityA->id,
        ]);
        $campaignB = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $cityB->id,
        ]);

        $propertyA = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $cityA->id,
                'sector_id' => $sectorA->id,
            ])->id,
        ]);

        $propertyB = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $cityB->id,
                'sector_id' => $sectorB->id,
            ])->id,
        ]);

        Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaignA->id,
            'property_id' => $propertyA->id,
            'user_id' => $admin->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED,
            'visited_at' => now()->subDays(2),
        ]);

        Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaignB->id,
            'property_id' => $propertyB->id,
            'user_id' => $admin->id,
            'status' => VisitStatus::INTERESTED,
            'visited_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard', [
            'city_id' => $cityA->id,
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertViewHas('metrics', function ($metrics) {
                return $metrics->visits_total === 1
                    && $metrics->installations_total === 1
                    && $metrics->interested_total === 0
                    && $metrics->conversion_rate === 100.0;
            });
    }

    public function test_manager_sees_ranking_conversion_and_sector_enrichment(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Ranking 44');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'manager-44@analytics.test']);
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'seller-a-44@analytics.test', 'name' => 'Aline Rank']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'seller-b-44@analytics.test', 'name' => 'Bruno Rank']);

        app(TenantContext::class)->set($company, $manager);

        $city = City::factory()->create(['company_id' => $company->id]);
        $sector = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Setor Norte 44',
        ]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $address = Address::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'sector_id' => $sector->id,
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
        ]);

        Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $sellerA->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED,
            'visited_at' => now(),
        ]);
        Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $sellerA->id,
            'status' => VisitStatus::INTERESTED,
            'visited_at' => now(),
        ]);
        Visit::factory()->count(5)->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $sellerB->id,
            'status' => VisitStatus::NO_INTEREST,
            'visited_at' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Aline Rank')
            ->assertSee('Conversão %')
            ->assertSee('Casas')
            ->assertSee('Setor Norte 44')
            ->assertSee('user_id='.$sellerA->id, false)
            ->assertSee('sector_id='.$sector->id, false)
            ->assertSee('💰 Comissões')
            ->assertDontSee('Módulo de comissão em preparação')
            ->assertViewHas('metrics', function ($metrics) use ($sellerA) {
                $first = $metrics->seller_productivity[0] ?? null;
                $sector = $metrics->sector_performance[0] ?? null;

                return $metrics->team_view === true
                    && $first
                    && $first['user_id'] === $sellerA->id
                    && $first['installations'] === 1
                    && isset($first['conversion_rate'])
                    && $sector
                    && $sector['properties_worked'] === 1
                    && isset($sector['installations'])
                    && isset($sector['conversion_rate']);
            });
    }

    public function test_seller_sees_only_own_results_without_team_blocks(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Seller Scope 44');
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'seller-scope-a@analytics.test', 'name' => 'Seller Scope A']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'seller-scope-b@analytics.test', 'name' => 'Seller Scope B']);

        app(TenantContext::class)->set($company, $sellerA);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $property = Property::factory()->create(['company_id' => $company->id]);

        Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $sellerA->id,
            'status' => VisitStatus::INTERESTED,
            'visited_at' => now(),
        ]);
        Visit::factory()->count(4)->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $sellerB->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED,
            'visited_at' => now(),
        ]);

        $this->actingAs($sellerA)
            ->get(route('dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Funil comercial')
            ->assertDontSee('Ranking de vendedores')
            ->assertDontSee('Desempenho por região')
            ->assertDontSee('Seller Scope B')
            ->assertViewHas('metrics', function ($metrics) {
                return $metrics->team_view === false
                    && $metrics->visits_total === 1
                    && $metrics->installations_total === 0
                    && $metrics->interested_total === 1
                    && $metrics->sector_performance === [];
            });
    }

    public function test_alerts_include_idle_sellers_and_pending_follow_ups(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Alerts 44');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'manager-alerts@analytics.test']);
        $idle = $this->makeUser($company, Role::SELLER, [
            'email' => 'idle-seller@analytics.test',
            'name' => 'Idle Seller 44',
        ]);

        app(TenantContext::class)->set($company, $manager);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $manager->id,
            'status' => VisitStatus::RETURN_LATER,
            'visited_at' => now()->subDay(),
        ]);

        foreach (range(1, 10) as $i) {
            FollowUp::factory()->create([
                'company_id' => $company->id,
                'visit_id' => $visit->id,
                'user_id' => $idle->id,
                'status' => FollowUpStatus::PENDING,
                'scheduled_at' => now()->addDays($i),
            ]);
        }

        $this->actingAs($manager)
            ->get(route('dashboard', ['period' => '30d']))
            ->assertOk()
            ->assertSee('Atenção')
            ->assertSee('Vendedor sem visitas hoje')
            ->assertSee('Idle Seller 44')
            ->assertSee('Muitos retornos pendentes');
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Sem Dashboard');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@analytics.test',
        ]);

        $role = Role::query()->where('slug', Role::VIEWER)->firstOrFail();
        $role->permissions()->sync(
            Permission::query()->where('slug', '!=', 'dashboard.view')->pluck('id')
        );

        $this->actingAs($viewer->fresh())
            ->get(route('dashboard'))
            ->assertForbidden();
    }
}
