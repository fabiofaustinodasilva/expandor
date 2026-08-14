<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\VisitStatus;
use App\Support\AppTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.34 — Seller mobile API + GPS / map adapter.
 */
class Sprint8234SellerMobileApiTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_bootstrap_returns_public_config_only(): void
    {
        [$seller, $token, $device] = $this->authenticatedSeller();

        $response = $this->mobileGet('/api/mobile/v1/bootstrap', $token, $device)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $seller->email)
            ->assertJsonPath('data.role', Role::SELLER)
            ->assertJsonPath('data.map.fallback', 'leaflet_osm')
            ->assertJsonPath('data.capabilities.offline', false)
            ->assertJsonPath('data.timezone.display', AppTime::zone())
            ->assertJsonStructure([
                'data' => [
                    'campaign_context' => ['campaigns', 'has_campaign', 'requires_selection'],
                    'sale_fields' => ['required', 'labels'],
                ],
            ]);

        $json = strtolower((string) $response->getContent());
        $this->assertStringNotContainsString('app_key', $json);
        $this->assertStringNotContainsString('smtp', $json);
        $this->assertStringNotContainsString('secret', $json);
        $this->assertIsArray($response->json('data.permissions'));
        $this->assertNotEmpty($response->json('data.map.attribution'));
    }

    public function test_markers_and_bbox(): void
    {
        $ctx = $this->opsContext();
        $inside = $this->makePoint($ctx, -23.5505, -46.6333, 'Casa Centro');
        $this->makePoint($ctx, -15.7800, -47.9300, 'Casa Brasilia');

        $all = $this->mobileGet('/api/mobile/v1/markers', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data.markers');
        $this->assertNotEmpty($all);
        $this->assertTrue(collect($all)->contains(fn ($m) => (int) $m['id'] === $inside->id));

        $bbox = $this->mobileGet(
            '/api/mobile/v1/markers?min_latitude=-23.56&max_latitude=-23.54&min_longitude=-46.64&max_longitude=-46.62',
            $ctx['token'],
            $ctx['device'],
        )->assertOk()->json('data.markers');

        $ids = collect($bbox)->pluck('id')->all();
        $this->assertContains($inside->id, $ids);
        $this->assertCount(1, $ids);
        $this->assertArrayHasKey('latitude', $bbox[0]);
        $this->assertArrayHasKey('status', $bbox[0]);
        $this->assertArrayHasKey('status_label', $bbox[0]);
    }

    public function test_point_list_search_and_detail(): void
    {
        $ctx = $this->opsContext();
        $point = $this->makePoint($ctx, -23.55, -46.63, 'Maria Silva', '11988887777');

        $list = $this->mobileGet('/api/mobile/v1/points', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1);
        $this->assertNotEmpty($list->json('data'));

        $search = $this->mobileGet('/api/mobile/v1/points?q=Maria', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $this->assertTrue(collect($search)->contains(fn ($row) => (int) ($row['property_id'] ?? $row['id']) === $point->id));

        $this->mobileGet('/api/mobile/v1/points/'.$point->id, $ctx['token'], $ctx['device'])
            ->assertOk()
            ->assertJsonPath('data.property_id', $point->id)
            ->assertJsonPath('data.resident_name', 'Maria Silva')
            ->assertJsonPath('data.resident_phone', '11988887777');
    }

    public function test_create_point_visit_follow_up_sale_and_commission(): void
    {
        $ctx = $this->opsContext();

        $created = $this->mobilePostJson('/api/mobile/v1/points', $ctx['token'], $ctx['device'], [
            'city_id' => $ctx['city']->id,
            'sector_id' => $ctx['sector']->id,
            'street' => 'Rua Mobile',
            'number' => '100',
            'latitude' => -23.551,
            'longitude' => -46.631,
            'status' => PropertyStatus::NEW->value,
            'contact_name' => 'João App',
            'contact_phone' => '11999990000',
        ])->assertCreated()->json('data');

        $pointId = (int) $created['property_id'];
        $this->assertSame(-23.551, (float) $created['latitude']);

        $this->mobilePostJson("/api/mobile/v1/points/{$pointId}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::INTERESTED->value,
            'notes' => 'Demonstrou interesse',
        ])->assertCreated()->assertJsonPath('data.status', VisitStatus::INTERESTED->value);

        $followUpAt = AppTime::now()->addDay()->format('Y-m-d H:i:s');
        $return = $this->mobilePostJson("/api/mobile/v1/points/{$pointId}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => $followUpAt,
            'notes' => 'Voltar amanhã',
        ])->assertCreated();
        $this->assertNotEmpty($return->json('data.visit'));

        $sale = $this->mobilePostJson("/api/mobile/v1/points/{$pointId}/sales", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'customer_name' => 'João App',
            'customer_phone' => '11999990000',
            'items' => [['product_id' => $ctx['product']->id, 'quantity' => 1]],
        ])->assertCreated();

        $sale->assertJsonPath('data.status', VisitStatus::INSTALLATION_REQUESTED->value);
        $this->assertNotEmpty($sale->json('data.sale_id'));
        $this->assertNotEmpty($sale->json('data.items'));
        $this->assertTrue((bool) $sale->json('data.commission_awarded.awarded'));
        $this->assertNotEmpty($sale->json('data.commission_id'));
        $this->assertGreaterThan(0, (float) $sale->json('data.commission_amount'));

        $this->mobilePostJson("/api/mobile/v1/points/{$pointId}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::NOT_HOME->value,
            'notes' => 'Porta fechada',
        ])->assertCreated()->assertJsonPath('data.status', VisitStatus::NOT_HOME->value);

        $this->mobilePostJson("/api/mobile/v1/points/{$pointId}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::NO_INTEREST->value,
            'notes' => 'Sem interesse no produto',
        ])->assertCreated()->assertJsonPath('data.status', VisitStatus::NO_INTEREST->value);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $ctx['product']->id,
            'quantity' => 1,
        ]);
    }

    public function test_products_agenda_commissions_and_results(): void
    {
        $ctx = $this->opsContext();
        Product::factory()->inactive()->create([
            'company_id' => $ctx['seller']->company_id,
            'name' => 'Plano morto',
        ]);

        $products = $this->mobileGet('/api/mobile/v1/products', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $names = collect($products)->pluck('name')->all();
        $this->assertContains($ctx['product']->name, $names);
        $this->assertNotContains('Plano morto', $names);

        $point = $this->makePoint($ctx, -23.55, -46.63, 'Agenda Cliente');
        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => AppTime::now()->format('Y-m-d H:i:s'),
        ])->assertCreated();

        $agenda = $this->mobileGet('/api/mobile/v1/agenda?scope=today', $ctx['token'], $ctx['device'])
            ->assertOk();
        $this->assertNotEmpty($agenda->json('data'));
        $this->assertArrayHasKey('current_page', $agenda->json('meta'));

        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'customer_name' => 'Agenda Cliente',
            'customer_phone' => '11911112222',
            'items' => [['product_id' => $ctx['product']->id, 'quantity' => 1]],
        ])->assertCreated();

        $commissions = $this->mobileGet('/api/mobile/v1/commissions', $ctx['token'], $ctx['device'])
            ->assertOk();
        $this->assertNotEmpty($commissions->json('data.items'));

        $this->mobileGet('/api/mobile/v1/results', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['visits_today', 'pending_follow_ups', 'active_campaigns', 'commissions']]);
    }

    public function test_cross_tenant_and_seller_only(): void
    {
        $ctx = $this->opsContext();
        $other = $this->makeCompanyWithPlan('Outra Empresa 8234');
        $foreign = Property::factory()->create([
            'company_id' => $other->id,
            'latitude' => -23.55,
            'longitude' => -46.63,
            'created_by' => User::factory()->create([
                'company_id' => $other->id,
                'role_id' => $ctx['seller']->role_id,
            ])->id,
        ]);

        $this->mobileGet('/api/mobile/v1/points/'.$foreign->id, $ctx['token'], $ctx['device'])
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');

        $adminCompany = $this->makeCompanyWithPlan('Admin 8234');
        $admin = $this->makeUser($adminCompany, Role::ADMINISTRATOR, [
            'email' => 'admin.8234@test',
            'password' => 'password',
        ]);
        $this->postJson('/api/mobile/v1/login', [
            'email' => $admin->email,
            'password' => 'password',
            'device_id' => $this->deviceId(9),
        ])->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson('/api/mobile/v1/bootstrap')->assertForbidden();
    }

    public function test_device_binding_and_session_replaced(): void
    {
        $seller = $this->makeSeller('seller.bind@8234.test');
        $deviceA = $this->deviceId(1);
        $deviceB = $this->deviceId(2);
        $tokenA = $this->mobileLogin($seller, $deviceA)->json('data.token');
        $tokenB = $this->mobileLogin($seller, $deviceB)->json('data.token');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenA,
            'Accept' => 'application/json',
        ])->getJson('/api/mobile/v1/bootstrap')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');

        $this->mobileGet('/api/mobile/v1/bootstrap', $tokenA, $deviceA)
            ->assertUnauthorized()
            ->assertJsonPath('code', 'session_replaced');

        $this->mobileGet('/api/mobile/v1/bootstrap', $tokenB, $deviceB)
            ->assertOk();
    }

    public function test_validation_errors_use_contract(): void
    {
        $ctx = $this->opsContext();

        $this->mobilePostJson('/api/mobile/v1/points', $ctx['token'], $ctx['device'], [
            'street' => 'Sem GPS',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonStructure(['message', 'errors']);

        $point = $this->makePoint($ctx, -23.55, -46.63, 'Validar');
        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
        ])->assertStatus(422)
            ->assertJsonPath('code', 'validation_error');
    }

    public function test_markers_avoid_obvious_n_plus_one(): void
    {
        $ctx = $this->opsContext();
        for ($i = 0; $i < 8; $i++) {
            $this->makePoint($ctx, -23.55 + ($i * 0.001), -46.63, 'Ponto '.$i);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->mobileGet('/api/mobile/v1/markers', $ctx['token'], $ctx['device'])->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThan(25, $count, 'markers query count was '.$count);
    }

    public function test_frontend_location_map_api_shell_and_offline_contracts(): void
    {
        $location = (string) file_get_contents(resource_path('js/mobile/location-service.js'));
        $this->assertStringContainsString('export const LocationService', $location);
        $this->assertStringContainsString('getCurrentPosition', $location);
        $this->assertStringContainsString('navigator.geolocation', $location);
        $this->assertStringContainsString('Capacitor?.Plugins?.Geolocation', $location);
        $this->assertStringContainsString('permission_denied', $location);
        $this->assertStringContainsString('Não foi possível acessar sua localização.', $location);
        $this->assertStringNotContainsString('watchPosition', $location);

        $adapter = (string) file_get_contents(resource_path('js/mobile/map-adapter.js'));
        $this->assertStringContainsString('export const MapAdapter', $adapter);
        $this->assertStringContainsString('renderMarkers', $adapter);
        $this->assertStringContainsString('recenterGps', $adapter);
        $this->assertStringContainsString('boundsQuery', $adapter);

        $api = (string) file_get_contents(resource_path('js/mobile/mobile-api.js'));
        $this->assertStringContainsString('bootstrap:', $api);
        $this->assertStringContainsString('markers:', $api);
        $this->assertStringContainsString('createPoint:', $api);
        $this->assertStringContainsString('visit:', $api);
        $this->assertStringContainsString('sale:', $api);
        $this->assertStringContainsString('agenda:', $api);
        $this->assertStringContainsString('products:', $api);
        $this->assertStringContainsString('commissions:', $api);
        $this->assertStringContainsString('network_offline', $api);
        $this->assertStringContainsString('Sem conexão. Esta ação ainda não pode ser concluída offline.', $api);

        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $this->assertStringContainsString('nav-map', $shell);
        $this->assertStringContainsString('nav-agenda', $shell);
        $this->assertStringContainsString('nav-clients', $shell);
        $this->assertStringContainsString('nav-results', $shell);
        $this->assertStringContainsString('nav-commissions', $shell);
        $this->assertStringContainsString('onCreatePoint', $shell);
        $this->assertStringContainsString('create-point-form', $shell);
        $this->assertStringContainsString('showReward', $shell);
        $this->assertStringContainsString('commission_awarded', $shell);
        $this->assertStringContainsString('400', $shell);

        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $this->assertStringContainsString('nav-map', $prepare);
        $this->assertStringContainsString('commission-coins.wav', $prepare);
        $this->assertStringContainsString('8.2.34', $prepare);
        $this->assertStringContainsString('capacitor-map-csp-hosts.mjs', $prepare);
        $this->assertStringContainsString('tile.openstreetmap.org', (string) file_get_contents(base_path('resources/js/mobile/map-tile-config.js')));

        $storage = (string) file_get_contents(resource_path('js/mobile/secure-auth-storage.js'));
        $this->assertDoesNotMatchRegularExpression('/localStorage\.(get|set|remove)/', $storage);

        $pkg = (string) file_get_contents(base_path('package.json'));
        $this->assertStringContainsString('"@capacitor/geolocation"', $pkg);

        $manifest = (string) file_get_contents(base_path('android/app/src/main/AndroidManifest.xml'));
        $this->assertStringContainsString('ACCESS_FINE_LOCATION', $manifest);
        $this->assertStringContainsString('ACCESS_COARSE_LOCATION', $manifest);
        $this->assertStringNotContainsString('ACCESS_BACKGROUND_LOCATION', $manifest);

        $this->assertSame([], glob(base_path('database/migrations/*8234*')) ?: []);
        $this->assertFileExists(base_path('docs/sprint-8234-seller-mobile-api-map/README.md'));
        $this->assertFileExists(base_path('docs/sprint-8234-seller-mobile-api-map/AUDIT.md'));
        $this->assertFileExists(base_path('docs/sprint-8234-seller-mobile-api-map/API-CONTRACT.md'));
    }

    /**
     * @return array{seller: User, token: string, device: string, city: City, sector: Sector, campaign: Campaign, product: Product}
     */
    private function opsContext(): array
    {
        [$seller, $token, $device] = $this->authenticatedSeller('seller.ops@8234.test');
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
        $product = Product::factory()->create([
            'company_id' => $companyId,
            'name' => '200 Mega Mobile',
            'commission_amount' => 40,
            'price' => 99.90,
        ]);

        return compact('seller', 'token', 'device', 'city', 'sector', 'campaign', 'product');
    }

    private function makePoint(array $ctx, float $lat, float $lng, string $name, string $phone = '11977776666'): Property
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
            'phone' => $phone,
            'is_primary_contact' => true,
        ]);

        return $property->fresh(['address', 'residents']);
    }

    /**
     * @return array{0: User, 1: string, 2: string}
     */
    private function authenticatedSeller(string $email = 'seller.boot@8234.test'): array
    {
        $seller = $this->makeSeller($email);
        $device = $this->deviceId(abs(crc32($email)) % 9000 + 1);
        $token = $this->mobileLogin($seller, $device)->json('data.token');

        return [$seller, $token, $device];
    }

    private function makeSeller(string $email, string $password = 'password'): User
    {
        $company = $this->makeCompanyWithPlan('Empresa 8234 '.$email);

        return $this->makeUser($company, Role::SELLER, [
            'email' => $email,
            'password' => $password,
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
