<?php

namespace Tests\Feature\Visits;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Models\PropertyHistory;
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

class VisitsModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_a_cannot_see_visits_from_company_b(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Visitas A');
        $companyB = $this->makeCompanyWithPlan('Empresa Visitas B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@visits.test',
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
            'notes' => 'Nota exclusiva Alpha Visita',
        ]);

        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, [
            'email' => 'admin-b@visits.test',
        ]);

        Visit::factory()->create([
            'company_id' => $companyB->id,
            'campaign_id' => $campaignB->id,
            'property_id' => $propertyB->id,
            'user_id' => $adminB->id,
            'notes' => 'Nota exclusiva Beta Visita',
        ]);

        $response = $this->actingAs($adminA)->get(route('campaigns.visits.index', $campaignA));

        $response->assertOk()
            ->assertSee('Nota exclusiva Alpha Visita')
            ->assertDontSee('Nota exclusiva Beta Visita');

        $foreignVisit = Visit::withoutGlobalScopes()
            ->where('company_id', $companyB->id)
            ->firstOrFail();

        $this->actingAs($adminA)
            ->get(route('visits.show', $foreignVisit))
            ->assertNotFound();
    }

    public function test_visit_updates_property_history(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Hist Visitas');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-history@visits.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $city = City::factory()->create(['company_id' => $company->id]);
        $sector = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);

        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $campaign->sectors()->attach($sector->id);

        $property = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NEW,
            'address_id' => \App\Domains\Sales\Properties\Models\Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
                'sector_id' => $sector->id,
            ])->id,
        ]);

        $response = $this->actingAs($admin)->post(route('campaigns.visits.store', $campaign), [
            'property_id' => $property->id,
            'status' => VisitStatus::INTERESTED->value,
            'notes' => 'Cliente pediu proposta.',
            'visited_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $visit = Visit::query()->where('property_id', $property->id)->firstOrFail();
        $response->assertRedirect(route('visits.show', $visit));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => PropertyStatus::INTERESTED->value,
        ]);

        $this->assertDatabaseHas('property_histories', [
            'property_id' => $property->id,
            'user_id' => $admin->id,
            'old_status' => PropertyStatus::NEW->value,
            'new_status' => PropertyStatus::INTERESTED->value,
        ]);

        $this->assertTrue(
            PropertyHistory::query()
                ->where('property_id', $property->id)
                ->where('description', 'like', '%Cliente pediu proposta.%')
                ->exists()
        );
    }

    public function test_follow_up_belongs_to_correct_visit(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa FollowUp');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-followup@visits.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

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
            'user_id' => $admin->id,
            'status' => VisitStatus::RETURN_LATER,
        ]);

        $response = $this->actingAs($admin)->post(route('visits.follow-ups.store', $visit), [
            'scheduled_date' => now()->addDays(2)->format('Y-m-d'),
            'scheduled_time' => '14:30',
            'notes' => 'Retornar com tabela de preços',
        ]);

        $response->assertRedirect(route('visits.show', $visit));

        $followUp = FollowUp::query()->where('visit_id', $visit->id)->firstOrFail();

        $this->assertSame($visit->id, $followUp->visit_id);
        $this->assertSame($company->id, $followUp->company_id);
        $this->assertSame(FollowUpStatus::PENDING, $followUp->status);
        $this->assertTrue($followUp->visit->is($visit));

        $this->actingAs($admin)
            ->post(route('follow-ups.complete', $followUp), [
                'status' => VisitStatus::INTERESTED->value,
                'notes' => 'Concluído via agenda',
            ])
            ->assertRedirect(route('follow-ups.index'));

        $this->assertDatabaseHas('follow_ups', [
            'id' => $followUp->id,
            'status' => FollowUpStatus::COMPLETED->value,
        ]);

        $this->assertDatabaseHas('visits', [
            'property_id' => $property->id,
            'status' => VisitStatus::INTERESTED->value,
            'notes' => 'Concluído via agenda',
        ]);
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Viewer Visitas');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@visits.test',
        ]);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('campaigns.visits.index', $campaign))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('follow-ups.index'))
            ->assertForbidden();
    }
}
