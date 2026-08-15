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
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class ExpVendedorDeletedPropertyAgendaSyncTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_web_delete_cancels_pending_follow_up_and_hides_from_mobile_agenda(): void
    {
        $ctx = $this->opsContext();
        $point = $this->makePoint($ctx, -23.55, -46.63, 'Casa Agenda');

        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => now()->addDay()->format('Y-m-d 12:00:00'),
        ])->assertCreated();

        $agenda = $this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $agenda);
        $followUpId = (int) $agenda[0]['id'];
        $this->assertSame($point->id, (int) $agenda[0]['property_id']);

        $visitId = FollowUp::query()->findOrFail($followUpId)->visit_id;

        $admin = $this->makeUser($ctx['seller']->company, Role::ADMINISTRATOR, [
            'email' => 'admin.agenda-sync@test',
        ]);
        app(TenantContext::class)->set($ctx['seller']->company, $admin);

        $this->actingAs($admin)
            ->deleteJson(route('map.points.destroy', $point), ['reason' => 'QA removeu o ponto'])
            ->assertOk();

        $this->assertSoftDeleted('properties', ['id' => $point->id]);
        $this->assertDatabaseHas('follow_ups', [
            'id' => $followUpId,
            'status' => FollowUpStatus::CANCELLED->value,
        ]);
        $this->assertDatabaseHas('visits', ['id' => $visitId]);
        $this->assertDatabaseHas('addresses', ['id' => $point->address_id]);
        $this->assertDatabaseHas('residents', ['property_id' => $point->id]);

        $this->isolateMobileClient();
        $after = $this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $this->assertSame([], $after);

        $this->mobileGet('/api/mobile/v1/points/'.$point->id, $ctx['token'], $ctx['device'])
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found')
            ->assertJsonPath('message', 'Este ponto não está mais disponível.');

        $markers = $this->mobileGet('/api/mobile/v1/markers', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data.markers');
        $this->assertFalse(collect($markers)->contains(fn ($row) => (int) ($row['id'] ?? $row['property_id'] ?? 0) === $point->id));

        $clients = $this->mobileGet('/api/mobile/v1/points', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $this->assertFalse(collect($clients)->contains(fn ($row) => (int) ($row['property_id'] ?? $row['id'] ?? 0) === $point->id));
    }

    public function test_agenda_filters_pending_follow_up_when_property_is_already_soft_deleted(): void
    {
        $ctx = $this->opsContext();
        $point = $this->makePoint($ctx, -23.55, -46.63, 'Casa Orfao');

        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => now()->addDay()->format('Y-m-d 12:00:00'),
        ])->assertCreated();

        $followUp = FollowUp::query()->where('user_id', $ctx['seller']->id)->firstOrFail();
        $this->assertSame(FollowUpStatus::PENDING, $followUp->status);

        $point->delete();

        $this->assertDatabaseHas('follow_ups', [
            'id' => $followUp->id,
            'status' => FollowUpStatus::PENDING->value,
        ]);

        $after = $this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $this->assertSame([], $after);
    }

    public function test_frontend_replaces_lists_on_refresh_and_keeps_last_state_on_network_error(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $adapter = (string) file_get_contents(resource_path('js/mobile/map-adapter.js'));

        $this->assertStringContainsString('async function loadAgenda()', $shell);
        $this->assertStringContainsString("mobileApi.agenda({ scope: 'today' })", $shell);
        $this->assertStringContainsString("nav-agenda", $shell);
        $this->assertStringContainsString('refreshOperational', $shell);
        $this->assertStringContainsString('visibilitychange', $shell);
        $this->assertStringContainsString('appStateChange', $shell);
        $this->assertStringContainsString('Não foi possível atualizar a Agenda.', $shell);
        $this->assertStringContainsString('Este ponto não está mais disponível.', $shell);
        $this->assertStringContainsString('handleUnavailablePoint', $shell);
        $this->assertStringContainsString('error.status === 404', $shell);
        $this->assertStringContainsString('isNetworkRefreshError', $shell);
        $this->assertStringContainsString('el.innerHTML = items.map(row).join', $shell);
        $this->assertStringContainsString('clearLayers', $adapter);
        $this->assertStringContainsString('this.markerRegistry.clear()', $adapter);
        $this->assertStringNotContainsString('upsertMarker', $adapter);
    }

    /**
     * @return array{seller: User, token: string, device: string, city: City, sector: Sector, campaign: Campaign}
     */
    private function opsContext(): array
    {
        $seller = $this->makeSeller('seller.agenda-sync@test');
        $device = $this->deviceId(42);
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
        $company = $this->makeCompanyWithPlan('Empresa Agenda Sync');

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
