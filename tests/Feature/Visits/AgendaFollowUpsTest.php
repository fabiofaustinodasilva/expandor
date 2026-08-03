<?php

namespace Tests\Feature\Visits;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class AgendaFollowUpsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_sees_only_own_follow_ups_on_agenda(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda Seller');
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'seller-a@agenda.test']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'seller-b@agenda.test']);

        app(TenantContext::class)->set($company, $sellerA);

        [$followA, $followB] = $this->makeTwoFollowUps($company, $sellerA, $sellerB);

        $this->actingAs($sellerA)
            ->get(route('follow-ups.index'))
            ->assertOk()
            ->assertSee('Agenda')
            ->assertSee((string) $followA->notes)
            ->assertDontSee((string) $followB->notes);
    }

    public function test_manager_sees_team_follow_ups_on_agenda(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda Manager');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'manager@agenda.test']);
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'seller-m-a@agenda.test']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'seller-m-b@agenda.test']);

        app(TenantContext::class)->set($company, $manager);

        [$followA, $followB] = $this->makeTwoFollowUps($company, $sellerA, $sellerB);

        $this->actingAs($manager)
            ->get(route('follow-ups.index'))
            ->assertOk()
            ->assertSee((string) $followA->notes)
            ->assertSee((string) $followB->notes)
            ->assertSee($sellerA->name)
            ->assertSee($sellerB->name);
    }

    public function test_complete_follow_up_creates_visit_and_updates_property(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda Complete');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-complete@agenda.test']);

        app(TenantContext::class)->set($company, $seller);

        $followUp = $this->makeFollowUpFor($company, $seller, 'Retorno para fechar');

        $this->actingAs($seller)
            ->post(route('follow-ups.complete', $followUp), [
                'status' => VisitStatus::INTERESTED->value,
                'notes' => 'Pediu proposta por WhatsApp',
            ])
            ->assertRedirect(route('follow-ups.index'));

        $this->assertDatabaseHas('follow_ups', [
            'id' => $followUp->id,
            'status' => FollowUpStatus::COMPLETED->value,
        ]);

        $this->assertDatabaseHas('visits', [
            'property_id' => $followUp->visit->property_id,
            'user_id' => $seller->id,
            'status' => VisitStatus::INTERESTED->value,
            'notes' => 'Pediu proposta por WhatsApp',
        ]);

        $this->assertDatabaseHas('properties', [
            'id' => $followUp->visit->property_id,
            'status' => PropertyStatus::INTERESTED->value,
        ]);

        $this->assertSame(2, Visit::query()->where('property_id', $followUp->visit->property_id)->count());
    }

    public function test_complete_with_return_later_schedules_new_follow_up(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda Reagendar');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-reschedule@agenda.test']);

        app(TenantContext::class)->set($company, $seller);

        $followUp = $this->makeFollowUpFor($company, $seller, 'Primeiro retorno');
        $when = now()->addDays(3)->format('Y-m-d\T10:30');

        $this->actingAs($seller)
            ->post(route('follow-ups.complete', $followUp), [
                'status' => VisitStatus::RETURN_LATER->value,
                'notes' => 'Cliente pediu mais um dia',
                'follow_up_at' => $when,
                'follow_up_notes' => 'Levar tabela',
            ])
            ->assertRedirect(route('follow-ups.index'));

        $this->assertDatabaseHas('follow_ups', [
            'id' => $followUp->id,
            'status' => FollowUpStatus::COMPLETED->value,
        ]);

        $newVisit = Visit::query()
            ->where('property_id', $followUp->visit->property_id)
            ->where('status', VisitStatus::RETURN_LATER->value)
            ->latest('id')
            ->first();

        $this->assertNotNull($newVisit);

        $this->assertDatabaseHas('follow_ups', [
            'visit_id' => $newVisit->id,
            'user_id' => $seller->id,
            'status' => FollowUpStatus::PENDING->value,
            'notes' => 'Levar tabela',
        ]);
    }

    public function test_agenda_shows_date_without_midnight_clock(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda Horario');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-horario@agenda.test']);

        app(TenantContext::class)->set($company, $seller);

        $followUp = $this->makeFollowUpFor($company, $seller, 'Só data');
        $followUp->update([
            'scheduled_at' => now()->addDays(2)->startOfDay(),
        ]);

        $label = $followUp->fresh()->scheduleLabel();

        $this->assertStringNotContainsString('00:00', $label);
        $this->assertSame('Horário não definido', $followUp->fresh()->scheduleTimeHint());

        $this->actingAs($seller)
            ->get(route('follow-ups.index'))
            ->assertOk()
            ->assertSee($label, false)
            ->assertSee('Horário não definido', false)
            ->assertDontSee($label.' 00:00', false)
            ->assertDontSee('00:00:00', false);
    }

    public function test_complete_return_later_accepts_date_without_time(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda DataOnly');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-dateonly@agenda.test']);

        app(TenantContext::class)->set($company, $seller);

        $followUp = $this->makeFollowUpFor($company, $seller, 'Reagendar só data');
        $when = now()->addDays(5)->format('Y-m-d');

        $this->actingAs($seller)
            ->post(route('follow-ups.complete', $followUp), [
                'status' => VisitStatus::RETURN_LATER->value,
                'follow_up_at' => $when,
            ])
            ->assertRedirect(route('follow-ups.index'));

        $new = FollowUp::query()
            ->where('status', FollowUpStatus::PENDING)
            ->where('user_id', $seller->id)
            ->where('notes', null)
            ->latest('id')
            ->first();

        $this->assertNotNull($new);
        $this->assertFalse($new->hasScheduledTime());
        $this->assertSame('Horário não definido', $new->scheduleTimeHint());
        $this->assertStringNotContainsString('00:00', $new->scheduleLabel());
    }

    public function test_store_follow_up_with_optional_time(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda StoreTime');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-store-time@agenda.test']);

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

        $date = now()->addDays(4)->format('Y-m-d');

        $this->actingAs($admin)
            ->post(route('visits.follow-ups.store', $visit), [
                'scheduled_date' => $date,
                'notes' => 'Sem hora marcada',
            ])
            ->assertRedirect(route('visits.show', $visit));

        $created = FollowUp::query()->where('visit_id', $visit->id)->latest('id')->firstOrFail();
        $this->assertFalse($created->hasScheduledTime());

        $this->actingAs($admin)
            ->post(route('visits.follow-ups.store', $visit), [
                'scheduled_date' => $date,
                'scheduled_time' => '15:45',
                'notes' => 'Com hora',
            ])
            ->assertRedirect(route('visits.show', $visit));

        $withTime = FollowUp::query()->where('visit_id', $visit->id)->where('notes', 'Com hora')->firstOrFail();
        $this->assertTrue($withTime->hasScheduledTime());
        $this->assertStringContainsString('às 15:45', $withTime->scheduleLabel());
    }

    public function test_complete_return_later_requires_follow_up_at(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda Validação');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-valid@agenda.test']);

        app(TenantContext::class)->set($company, $seller);

        $followUp = $this->makeFollowUpFor($company, $seller, 'Sem data');

        $this->actingAs($seller)
            ->from(route('follow-ups.index'))
            ->post(route('follow-ups.complete', $followUp), [
                'status' => VisitStatus::RETURN_LATER->value,
                'notes' => 'Faltou agendar',
            ])
            ->assertSessionHasErrors('follow_up_at');

        $this->assertDatabaseHas('follow_ups', [
            'id' => $followUp->id,
            'status' => FollowUpStatus::PENDING->value,
        ]);
    }

    public function test_seller_cannot_complete_another_sellers_follow_up(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda Forbidden');
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'seller-forb-a@agenda.test']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'seller-forb-b@agenda.test']);

        app(TenantContext::class)->set($company, $sellerA);

        $followB = $this->makeFollowUpFor($company, $sellerB, 'Do outro vendedor');

        $this->actingAs($sellerA)
            ->post(route('follow-ups.complete', $followB), [
                'status' => VisitStatus::NO_INTEREST->value,
            ])
            ->assertForbidden();
    }

    public function test_map_point_show_includes_next_follow_up(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda Drawer');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-drawer@agenda.test']);

        app(TenantContext::class)->set($company, $seller);

        $followUp = $this->makeFollowUpFor($company, $seller, 'Drawer note');
        $propertyId = $followUp->visit->property_id;

        Resident::factory()->create([
            'company_id' => $company->id,
            'property_id' => $propertyId,
            'name' => 'Maria Drawer',
            'phone' => '11999990000',
            'is_primary_contact' => true,
        ]);

        $this->actingAs($seller)
            ->getJson(route('map.points.show', $propertyId))
            ->assertOk()
            ->assertJsonPath('data.next_follow_up_notes', 'Drawer note')
            ->assertJsonStructure([
                'data' => [
                    'next_follow_up_at',
                    'next_follow_up_relative',
                    'next_follow_up_notes',
                ],
            ]);
    }

    /**
     * @return array{0: FollowUp, 1: FollowUp}
     */
    protected function makeTwoFollowUps($company, $sellerA, $sellerB): array
    {
        return [
            $this->makeFollowUpFor($company, $sellerA, 'Nota exclusiva A'),
            $this->makeFollowUpFor($company, $sellerB, 'Nota exclusiva B'),
        ];
    }

    protected function makeFollowUpFor($company, $user, string $notes): FollowUp
    {
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
            'user_id' => $user->id,
            'status' => VisitStatus::RETURN_LATER,
        ]);

        return FollowUp::factory()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'user_id' => $user->id,
            'scheduled_at' => now()->addDay(),
            'status' => FollowUpStatus::PENDING,
            'notes' => $notes,
        ]);
    }
}
