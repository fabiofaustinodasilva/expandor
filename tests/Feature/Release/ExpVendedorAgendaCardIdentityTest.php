<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Enums\ResidentStatus;
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

class ExpVendedorAgendaCardIdentityTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_agenda_returns_primary_resident_and_visit_seller_not_logged_user(): void
    {
        $ctx = $this->opsContext('Carlos Henrique');
        $followUpAt = now()->addDay()->format('Y-m-d 09:30:00');

        $this->mobilePostJson('/api/mobile/v1/first-approach', $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'city_id' => $ctx['city']->id,
            'street' => 'Rua Goiás',
            'number' => '123',
            'neighborhood' => 'Centro',
            'latitude' => -23.551,
            'longitude' => -46.631,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => $followUpAt,
            'contact_name' => 'João da Silva',
            'contact_phone' => '64999998888',
        ])->assertCreated();

        $item = $this->agendaItem($ctx);
        $this->assertSame('João da Silva', $item['contact_name']);
        $this->assertSame('64999998888', $item['contact_phone']);
        $this->assertSame($ctx['seller']->id, (int) $item['seller_id']);
        $this->assertSame('Carlos Henrique', $item['seller_name']);
        $this->assertStringContainsString('Rua Goiás', (string) $item['address']);
        $this->assertArrayNotHasKey('document', $item);
        $this->assertArrayNotHasKey('cpf', $item);
        $json = json_encode($item);
        $this->assertStringNotContainsString('cpf', strtolower((string) $json));

        $other = $this->makeUser($ctx['seller']->company, Role::SELLER, [
            'name' => 'Maria Oliveira',
            'email' => 'maria.agenda-card@test',
            'password' => 'password',
        ]);
        $visit = Visit::query()->where('user_id', $ctx['seller']->id)->firstOrFail();
        $visit->forceFill(['user_id' => $other->id])->save();

        $swapped = $this->agendaItem($ctx);
        $this->assertSame('Maria Oliveira', $swapped['seller_name']);
        $this->assertSame($other->id, (int) $swapped['seller_id']);
        $this->assertNotSame($ctx['seller']->id, (int) $swapped['seller_id']);
    }

    public function test_agenda_contact_fallback_phone_then_unnamed(): void
    {
        $ctx = $this->opsContext('Carlos Henrique');
        $point = $this->makePoint($ctx, 'Sem Nome');
        Resident::query()->where('property_id', $point->id)->update([
            'name' => '',
            'phone' => '64911112222',
            'is_primary_contact' => true,
            'status' => ResidentStatus::ACTIVE,
        ]);

        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => now()->addDay()->format('Y-m-d 12:00:00'),
        ])->assertCreated();

        $withPhone = $this->agendaItem($ctx);
        $this->assertSame('64911112222', $withPhone['contact_name']);

        Resident::query()->where('property_id', $point->id)->update(['name' => '', 'phone' => null, 'whatsapp' => null]);
        $unnamed = $this->agendaItem($ctx);
        $this->assertSame('Cliente sem nome', $unnamed['contact_name']);
        $this->assertNull($unnamed['contact_phone']);
    }

    public function test_two_future_returns_keep_identity_and_deleted_property_drops_card(): void
    {
        $ctx = $this->opsContext('Carlos Henrique');
        $at = now()->addDays(2)->format('Y-m-d 10:00:00');

        $joao = $this->mobilePostJson('/api/mobile/v1/first-approach', $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'city_id' => $ctx['city']->id,
            'street' => 'Rua João',
            'latitude' => -23.551,
            'longitude' => -46.631,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => $at,
            'contact_name' => 'João da Silva',
        ])->assertCreated()->json('data.property_id');

        $this->mobilePostJson('/api/mobile/v1/first-approach', $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'city_id' => $ctx['city']->id,
            'street' => 'Rua Maria',
            'latitude' => -23.552,
            'longitude' => -46.632,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => $at,
            'contact_name' => 'Maria Oliveira',
        ])->assertCreated();

        $names = collect($this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])->json('data'))
            ->pluck('contact_name')
            ->all();
        $this->assertContains('João da Silva', $names);
        $this->assertContains('Maria Oliveira', $names);

        $admin = $this->makeUser($ctx['seller']->company, Role::ADMINISTRATOR, [
            'email' => 'admin.agenda-card@test',
        ]);
        app(TenantContext::class)->set($ctx['seller']->company, $admin);
        $this->actingAs($admin)->deleteJson(route('map.points.destroy', $joao))->assertOk();

        $this->isolateMobileClient();
        $after = collect($this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])->json('data'))
            ->pluck('contact_name')
            ->all();
        $this->assertNotContains('João da Silva', $after);
        $this->assertContains('Maria Oliveira', $after);
        $this->assertDatabaseHas('follow_ups', [
            'status' => FollowUpStatus::CANCELLED->value,
        ]);
    }

    public function test_shell_renders_contact_seller_hierarchy_without_cpf_and_keeps_scope_all(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $this->assertStringContainsString("mobileApi.agenda({ scope: 'all' })", $shell);
        $this->assertStringContainsString('agendaContactLabel', $shell);
        $this->assertStringContainsString('contact_name', $shell);
        $this->assertStringContainsString('seller_name', $shell);
        $this->assertStringContainsString('Atendido por:', $shell);
        $this->assertStringContainsString('${seller ?', $shell);
        $agendaFn = explode('async function loadClients', explode('async function loadAgenda', $shell)[1] ?? '')[0] ?? '';
        $this->assertStringContainsString('data-complete-follow-up', $agendaFn);
        $this->assertStringContainsString('data-open-point', $agendaFn);
        $this->assertStringNotContainsString('cpf', strtolower($agendaFn));
        $this->assertStringNotContainsString('document', strtolower($agendaFn));
    }

    /**
     * @return array<string, mixed>
     */
    private function agendaItem(array $ctx): array
    {
        $data = $this->mobileGet('/api/mobile/v1/agenda?scope=all', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $this->assertNotEmpty($data);

        return $data[0];
    }

    /**
     * @return array{seller: User, token: string, device: string, city: City, sector: Sector, campaign: Campaign}
     */
    private function opsContext(string $sellerName): array
    {
        $company = $this->makeCompanyWithPlan('Empresa Agenda Card');
        $seller = $this->makeUser($company, Role::SELLER, [
            'name' => $sellerName,
            'email' => 'seller.agenda-card@test',
            'password' => 'password',
        ]);
        $device = sprintf('22222222-2222-4222-8222-%012d', 81);
        $token = $this->mobileLogin($seller, $device)->json('data.token');
        $city = City::factory()->create(['company_id' => $company->id]);
        $sector = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
            'name' => 'Única na sua porta',
        ]);
        $campaign->users()->sync([$seller->id]);
        $campaign->sectors()->attach($sector->id);

        return compact('seller', 'token', 'device', 'city', 'sector', 'campaign');
    }

    private function makePoint(array $ctx, string $name): Property
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
            'latitude' => -23.55,
            'longitude' => -46.63,
            'status' => PropertyStatus::NEW,
            'created_by' => $ctx['seller']->id,
        ]);
        Resident::factory()->create([
            'company_id' => $ctx['seller']->company_id,
            'property_id' => $property->id,
            'name' => $name,
            'phone' => '11977776666',
            'is_primary_contact' => true,
            'status' => ResidentStatus::ACTIVE,
        ]);

        return $property->fresh(['address', 'residents']);
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
