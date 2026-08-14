<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Handoff\OfficeSalesWhatsAppSettings;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\VisitStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class MobileSaleHandoffAfterFirstApproachTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_handoff_route_is_registered_and_unauthenticated_is_401_not_missing(): void
    {
        $this->assertTrue(
            Route::has('mobile.sales.handoff.show'),
            'GET api/mobile/v1/sales/{sale}/handoff must be registered',
        );

        $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri());
        $this->assertTrue($uris->contains('api/mobile/v1/sales/{sale}/handoff'));

        $missing = $this->getJson('/api/mobile/v1/sales/36/handoff');
        $missing->assertStatus(401)
            ->assertJsonPath('code', 'unauthenticated');
        $this->assertStringNotContainsString('could not be found', (string) $missing->getContent());
    }

    public function test_first_approach_sale_id_matches_sales_table_and_handoff_returns_200(): void
    {
        $ctx = $this->opsContext();
        app(OfficeSalesWhatsAppSettings::class)->save($ctx['seller']->company, '64999998888', true);

        $created = $this->mobilePostJson('/api/mobile/v1/first-approach', $ctx['token'], $ctx['device'], $this->firstApproachSalePayload($ctx))
            ->assertCreated();

        $saleId = (int) $created->json('data.sale_id');
        $this->assertGreaterThan(0, $saleId);
        $this->assertSame($saleId, (int) $created->json('data.office_handoff.sale_id'));

        $sale = Sale::query()->withoutGlobalScopes()->find($saleId);
        $this->assertNotNull($sale);
        $this->assertSame((int) $ctx['seller']->company_id, (int) $sale->company_id);
        $this->assertNotNull($sale->visit_id);
        $visit = $sale->visit()->withoutGlobalScopes()->first();
        $this->assertNotNull($visit);
        $this->assertSame((int) $sale->visit_id, (int) $visit->id);

        $handoff = $this->mobileGet("/api/mobile/v1/sales/{$saleId}/handoff", $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');

        $this->assertNotSame('', (string) ($handoff['message'] ?? ''));
        $this->assertTrue($handoff['copy_available']);
        $this->assertTrue($handoff['view_available']);
        $this->assertTrue($handoff['whatsapp_configured']);
        $this->assertTrue($handoff['can_send']);
        $this->assertNotEmpty($handoff['whatsapp_url']);
    }

    public function test_handoff_disabled_whatsapp_keeps_copy_and_view(): void
    {
        $ctx = $this->opsContext();
        app(OfficeSalesWhatsAppSettings::class)->save($ctx['seller']->company, '64999998888', false);

        $saleId = (int) $this->mobilePostJson('/api/mobile/v1/first-approach', $ctx['token'], $ctx['device'], $this->firstApproachSalePayload($ctx))
            ->assertCreated()
            ->json('data.sale_id');

        $handoff = $this->mobileGet("/api/mobile/v1/sales/{$saleId}/handoff", $ctx['token'], $ctx['device'])->assertOk()->json('data');
        $this->assertFalse($handoff['can_send']);
        $this->assertTrue($handoff['whatsapp_configured']);
        $this->assertTrue($handoff['copy_available']);
        $this->assertTrue($handoff['view_available']);
        $this->assertNull($handoff['whatsapp_url']);
    }

    public function test_handoff_without_number_is_not_configured(): void
    {
        $ctx = $this->opsContext();
        $ctx['seller']->company->update(['whatsapp' => null, 'phone' => null]);
        app(OfficeSalesWhatsAppSettings::class)->save($ctx['seller']->company->fresh(), '', true);

        $saleId = (int) $this->mobilePostJson('/api/mobile/v1/first-approach', $ctx['token'], $ctx['device'], $this->firstApproachSalePayload($ctx))
            ->assertCreated()
            ->json('data.sale_id');

        $handoff = $this->mobileGet("/api/mobile/v1/sales/{$saleId}/handoff", $ctx['token'], $ctx['device'])->assertOk()->json('data');
        $this->assertFalse($handoff['can_send']);
        $this->assertFalse($handoff['whatsapp_configured']);
        $this->assertTrue($handoff['copy_available']);
        $this->assertTrue($handoff['view_available']);
    }

    public function test_cross_tenant_handoff_is_forbidden_and_missing_sale_is_404(): void
    {
        $ctx = $this->opsContext();
        app(OfficeSalesWhatsAppSettings::class)->save($ctx['seller']->company, '64999998888', true);
        $saleId = (int) $this->mobilePostJson('/api/mobile/v1/first-approach', $ctx['token'], $ctx['device'], $this->firstApproachSalePayload($ctx))
            ->assertCreated()
            ->json('data.sale_id');

        $other = $this->makeCompanyWithPlan('Empresa Handoff 404');
        $otherSeller = $this->makeUser($other, Role::SELLER, ['email' => 'other-handoff-404@test']);
        $device = $this->deviceId(91);
        $token = $this->mobileLogin($otherSeller, $device)->json('data.token');

        $status = $this->mobileGet("/api/mobile/v1/sales/{$saleId}/handoff", $token, $device)->status();
        $this->assertContains($status, [401, 403, 404]);

        $this->mobileGet('/api/mobile/v1/sales/999999/handoff', $ctx['token'], $ctx['device'])
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    private function firstApproachSalePayload(array $ctx): array
    {
        return [
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'complete_sale' => true,
            'campaign_id' => $ctx['campaign']->id,
            'city_id' => $ctx['city']->id,
            'street' => 'Rua Goiás',
            'number' => '123',
            'latitude' => -16.7,
            'longitude' => -51.2,
            'items' => [['product_id' => $ctx['product']->id, 'quantity' => 1]],
            'customer_name' => 'João da Silva',
            'customer_document' => '529.982.247-25',
            'customer_birth_date' => '15/03/1990',
            'customer_phone' => '(64) 99999-9999',
            'install_street' => 'Rua Goiás',
            'install_number' => '123',
            'install_neighborhood' => 'Centro',
            'install_reference' => 'Próximo à praça',
            'install_city' => $ctx['city']->name,
            'due_day' => 10,
        ];
    }

    /**
     * @return array{seller: User, token: string, device: string, city: City, campaign: Campaign, product: Product}
     */
    private function opsContext(): array
    {
        $company = $this->makeCompanyWithPlan('Empresa First Approach Handoff');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.fa.handoff@test',
            'name' => 'Carlos Vendedor',
        ]);
        $device = $this->deviceId(42);
        $token = $this->mobileLogin($seller, $device)->json('data.token');
        $city = City::factory()->create(['company_id' => $company->id, 'name' => 'Bom Jardim de Goiás']);
        Sector::factory()->create(['company_id' => $company->id, 'city_id' => $city->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->sync([$seller->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => '500 Mega',
            'price' => 97.90,
            'commission_amount' => 50,
        ]);

        return compact('seller', 'token', 'device', 'city', 'campaign', 'product');
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
