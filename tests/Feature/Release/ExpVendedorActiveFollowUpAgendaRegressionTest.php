<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
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

class ExpVendedorActiveFollowUpAgendaRegressionTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_first_approach_return_later_creates_pending_follow_up_visible_on_mobile_agenda(): void
    {
        $ctx = $this->opsContext();
        $followUpAt = now()->addDay()->format('Y-m-d 09:00:00');

        $created = $this->mobilePostJson('/api/mobile/v1/first-approach', $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'city_id' => $ctx['city']->id,
            'street' => 'Rua Retorno Novo',
            'number' => '10',
            'latitude' => -23.551,
            'longitude' => -46.631,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => $followUpAt,
            'notes' => 'Voltar amanha',
        ])->assertCreated();

        $propertyId = (int) $created->json('data.property_id');
        $this->assertGreaterThan(0, $propertyId);

        $followUp = FollowUp::query()->where('user_id', $ctx['seller']->id)->first();
        $this->assertNotNull($followUp, 'CASO A: FollowUp nao foi criado no banco');
        $visit = Visit::query()->findOrFail($followUp->visit_id);
        $property = Property::query()->find($visit->property_id);

        $this->assertSame(FollowUpStatus::PENDING, $followUp->status);
        $this->assertSame($ctx['seller']->id, (int) $followUp->user_id);
        $this->assertNotNull($followUp->visit_id);
        $this->assertNotNull($visit->property_id);
        $this->assertNotNull($property);
        $this->assertNull($property->deleted_at);
        $this->assertSame((int) $ctx['seller']->company_id, (int) $followUp->company_id);
        $this->assertSame((int) $ctx['seller']->company_id, (int) $property->company_id);
        $this->assertSame($ctx['campaign']->id, (int) $visit->campaign_id);
        $this->assertTrue(
            FollowUp::query()->whereKey($followUp->id)->operationalPending()->exists(),
            'operationalPending excluiu follow-up valido',
        );

        $today = $this->mobileGet('/api/mobile/v1/agenda?scope=today', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $upcoming = $this->mobileGet('/api/mobile/v1/agenda?scope=upcoming', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $all = $this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');

        $idsAll = collect($all)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($followUp->id, $idsAll, 'CASO B: API agenda all nao devolveu o follow-up criado');
        $inToday = collect($today)->pluck('id')->contains($followUp->id);
        $inUpcoming = collect($upcoming)->pluck('id')->contains($followUp->id);
        $this->assertTrue($inToday || $inUpcoming, 'follow-up futuro/hoje deve aparecer em today ou upcoming');
        $this->assertFalse($inToday && $inUpcoming);

        $admin = $this->makeUser($ctx['seller']->company, Role::ADMINISTRATOR, [
            'email' => 'admin.agenda-regression@test',
        ]);
        app(TenantContext::class)->set($ctx['seller']->company, $admin);
        $this->actingAs($admin)
            ->deleteJson(route('map.points.destroy', $property))
            ->assertOk();

        $followUp->refresh();
        $this->assertSame(FollowUpStatus::CANCELLED, $followUp->status);
        $this->assertDatabaseHas('visits', ['id' => $visit->id]);

        $this->isolateMobileClient();
        $after = $this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $this->assertNotContains($followUp->id, collect($after)->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_two_valid_returns_and_filters_for_cancelled_completed_deleted_and_tenant(): void
    {
        $ctx = $this->opsContext();
        $followUpAt = now()->addDay()->format('Y-m-d 12:00:00');

        $first = $this->mobilePostJson('/api/mobile/v1/first-approach', $ctx['token'], $ctx['device'], $this->returnPayload($ctx, $followUpAt, 'Rua Um'))
            ->assertCreated();
        $existing = $this->makePoint($ctx, -23.552, -46.632, 'Ponto Existente');
        $this->mobilePostJson("/api/mobile/v1/points/{$existing->id}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => $followUpAt,
        ])->assertCreated();

        $nullSector = $this->mobilePostJson('/api/mobile/v1/first-approach', $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'city_id' => $ctx['city']->id,
            'street' => 'Rua Sem Setor',
            'latitude' => -23.553,
            'longitude' => -46.633,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => $followUpAt,
        ])->assertCreated();
        $this->assertNull(Address::query()->find(Property::query()->find($nullSector->json('data.property_id'))?->address_id)?->sector_id);

        $agenda = $this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $this->assertCount(3, $agenda);

        $completed = FollowUp::query()->where('user_id', $ctx['seller']->id)->orderBy('id')->first();
        $completed->update(['status' => FollowUpStatus::COMPLETED, 'completed_at' => now()]);
        $cancelled = FollowUp::query()->where('user_id', $ctx['seller']->id)->orderBy('id')->skip(1)->first();
        $cancelled->update(['status' => FollowUpStatus::CANCELLED]);

        $filtered = $this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $filteredIds = collect($filtered)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertNotContains($completed->id, $filteredIds);
        $this->assertNotContains($cancelled->id, $filteredIds);
        $this->assertCount(1, $filtered);

        $other = $this->makeCompanyWithPlan('Outra Empresa Agenda');
        FollowUp::query()->create([
            'company_id' => $other->id,
            'visit_id' => Visit::query()->where('user_id', $ctx['seller']->id)->value('id'),
            'user_id' => $ctx['seller']->id,
            'scheduled_at' => now()->addDay(),
            'status' => FollowUpStatus::PENDING,
        ]);
        $isolated = $this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $isolated);
    }

    public function test_shell_fetches_all_operational_pending_not_today_only(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $model = (string) file_get_contents(app_path('Domains/Visits/Models/FollowUp.php'));

        $this->assertStringContainsString("mobileApi.agenda({ scope: 'all' })", $shell);
        $this->assertStringNotContainsString("mobileApi.agenda({ scope: 'today' })", $shell);
        $this->assertStringContainsString("whereHas('property'", $model);
        $this->assertStringContainsString('whereNull(\'properties.deleted_at\')', $model);
        $this->assertStringContainsString('FollowUpStatus::PENDING', $model);
    }

    /**
     * @return array<string, mixed>
     */
    private function returnPayload(array $ctx, string $followUpAt, string $street): array
    {
        return [
            'campaign_id' => $ctx['campaign']->id,
            'city_id' => $ctx['city']->id,
            'street' => $street,
            'latitude' => -23.551,
            'longitude' => -46.631,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => $followUpAt,
        ];
    }

    /**
     * @return array{seller: User, token: string, device: string, city: City, sector: Sector, campaign: Campaign}
     */
    private function opsContext(): array
    {
        $seller = $this->makeSeller('seller.agenda-regression@test');
        $device = $this->deviceId(77);
        $token = $this->mobileLogin($seller, $device)->json('data.token');
        $companyId = (int) $seller->company_id;
        $city = City::factory()->create(['company_id' => $companyId]);
        $sector = Sector::factory()->create([
            'company_id' => $companyId,
            'city_id' => $city->id,
        ]);
        $campaign = Campaign::factory()->create([
            'company_id' => $companyId,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->sync([$seller->id]);
        $campaign->sectors()->attach($sector->id);

        return compact('seller', 'token', 'device', 'city', 'sector', 'campaign');
    }

    private function makePoint(array $ctx, float $lat, float $lng, string $name): Property
    {
        $address = Address::factory()->create([
            'company_id' => $ctx['seller']->company_id,
            'city_id' => $ctx['city']->id,
            'sector_id' => $ctx['sector']->id,
            'street' => 'Rua '.$name,
            'number' => '10',
        ]);
        $property = Property::factory()->create([
            'company_id' => $ctx['seller']->company_id,
            'address_id' => $address->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'status' => PropertyStatus::NEW,
            'created_by' => $ctx['seller']->id,
        ]);
        Resident::factory()->create([
            'company_id' => $ctx['seller']->company_id,
            'property_id' => $property->id,
            'name' => $name,
            'phone' => '11977776666',
            'is_primary_contact' => true,
        ]);

        return $property->fresh(['address', 'residents']);
    }

    private function makeSeller(string $email): User
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda Regression');

        return $this->makeUser($company, Role::SELLER, [
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function deviceId(int $n): string
    {
        return sprintf('22222222-2222-4222-8222-%012d', $n);
    }

    private function mobileLogin(User $user, string $deviceId)
    {
        return $this->postJson('/api/mobile/v1/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_id' => $deviceId,
            'device_name' => 'Android',
            'platform' => 'android',
            'app_version' => '8.2.34',
        ]);
    }

    private function mobileGet(string $uri, string $token, string $deviceId)
    {
        $this->isolateMobileClient();

        return $this->withHeaders($this->mobileHeaders($token, $deviceId))->getJson($uri);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mobilePostJson(string $uri, string $token, string $deviceId, array $payload)
    {
        $this->isolateMobileClient();

        return $this->withHeaders($this->mobileHeaders($token, $deviceId))->postJson($uri, $payload);
    }

    private function isolateMobileClient(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    /**
     * @return array<string, string>
     */
    private function mobileHeaders(string $token, string $deviceId): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'X-Device-Id' => $deviceId,
            'X-App-Version' => '8.2.34',
        ];
    }
}
