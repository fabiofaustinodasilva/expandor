<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Handoff\OfficeSalesWhatsAppSettings;
use App\Domains\Sales\Models\Sale;
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
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class ExpVendedorCompleteSaleHandoffTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_bootstrap_exposes_sale_fields_and_due_days(): void
    {
        $ctx = $this->opsContext();

        $this->mobileGet('/api/mobile/v1/bootstrap', $ctx['token'], $ctx['device'])
            ->assertOk()
            ->assertJsonPath('data.sale_fields.due_days', [5, 10, 15, 20, 25, 30])
            ->assertJsonPath('data.campaign_context.campaigns.0.city_id', $ctx['city']->id)
            ->assertJsonPath('data.campaign_context.campaigns.0.city_name', 'Bom Jardim de Goiás')
            ->assertJsonPath('data.campaign_context.active_city_id', $ctx['city']->id)
            ->assertJsonPath('data.campaign_context.active_city_name', 'Bom Jardim de Goiás')
            ->assertJsonStructure([
                'data' => [
                    'sale_fields' => ['required', 'labels', 'due_days'],
                    'office_handoff' => ['enabled', 'configured', 'whatsapp_enabled'],
                    'campaign_context' => ['campaigns', 'has_campaign', 'active_campaign_id', 'active_city_id', 'active_city_name'],
                ],
            ]);
    }

    public function test_sale_uses_campaign_city_not_client_forged_city(): void
    {
        $ctx = $this->opsContext();
        $otherCity = City::factory()->create([
            'company_id' => $ctx['seller']->company_id,
            'name' => 'Cidade Estranha',
        ]);
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Maria Antiga');
        $this->assertSame($ctx['city']->id, (int) $point->address->city_id);

        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
            'install_city' => 'Selecionar',
            'city_id' => $otherCity->id,
        ], $ctx))->assertCreated();

        $fresh = $point->fresh('address');
        $this->assertSame($ctx['city']->id, (int) $fresh->address->city_id);
        $this->assertEqualsWithDelta(-16.7, (float) $fresh->latitude, 0.0001);
        $this->assertEqualsWithDelta(-51.2, (float) $fresh->longitude, 0.0001);
        $this->assertSame('Rua Goiás', $fresh->address->street);
    }

    public function test_campaign_city_wins_over_property_city(): void
    {
        $ctx = $this->opsContext();
        $otherCity = City::factory()->create([
            'company_id' => $ctx['seller']->company_id,
            'name' => 'Outro Município',
        ]);
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Cliente');
        $point->address->update(['city_id' => $otherCity->id]);

        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
            'install_city' => 'Outro Município',
        ], $ctx))->assertCreated();

        $this->assertSame($ctx['city']->id, (int) $point->fresh('address')->address->city_id);
        $this->assertEqualsWithDelta(-16.7, (float) $point->fresh()->latitude, 0.0001);
    }

    public function test_cross_tenant_city_is_not_applied(): void
    {
        $ctx = $this->opsContext();
        $foreign = $this->makeCompanyWithPlan('Empresa Cidade Estrangeira');
        $foreignCity = City::factory()->create(['company_id' => $foreign->id, 'name' => 'Cidade de Fora']);
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Local');

        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
            'city_id' => $foreignCity->id,
            'install_city' => 'Cidade de Fora',
        ], $ctx))->assertCreated();

        $this->assertSame($ctx['city']->id, (int) $point->fresh('address')->address->city_id);
    }

    public function test_shell_prefills_campaign_city_and_never_copies_selecionar(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $cart = (string) file_get_contents(resource_path('js/mobile/sale-cart.js'));
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringContainsString('resolveSaleCity', $shell);
        $this->assertStringContainsString('campaignCityForSale', $shell);
        $this->assertStringContainsString('active_city_name', $shell);
        $this->assertStringContainsString('/^selecionar$/i', $shell);
        $this->assertStringContainsString('applySaleCity', $cart);
        $this->assertStringContainsString('Definida pela campanha', $prepare);
        $this->assertStringContainsString('Digite a cidade', $prepare);
        $this->assertStringNotContainsString("city: cityNameFromSelect()", $shell);
    }

    public function test_complete_sale_persists_client_address_due_day_products_and_handoff(): void
    {
        $ctx = $this->opsContext();
        app(OfficeSalesWhatsAppSettings::class)->save($ctx['seller']->company, '64999998888', true);
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Maria Antiga');

        $response = $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
        ], $ctx));

        $response->assertCreated()
            ->assertJsonPath('data.office_handoff.seller_name', $ctx['seller']->name)
            ->assertJsonPath('data.office_handoff.whatsapp_enabled', true)
            ->assertJsonPath('data.office_handoff.can_send', true)
            ->assertJsonPath('data.office_handoff.whatsapp_configured', true)
            ->assertJsonPath('data.office_handoff.copy_available', true)
            ->assertJsonPath('data.office_handoff.view_available', true)
            ->assertJsonPath('data.status', VisitStatus::INSTALLATION_REQUESTED->value);

        $saleId = (int) $response->json('data.office_handoff.sale_id');
        $this->assertSame($saleId, (int) $response->json('data.sale_id'));
        $this->assertSame($saleId, (int) $response->json('data.commission_awarded.sale_id'));
        $this->assertGreaterThan(0, (float) $response->json('data.commission_amount'));

        $resident = Resident::query()->where('property_id', $point->id)->where('name', 'João da Silva')->firstOrFail();
        $this->assertSame('(64) 99999-9999', $resident->phone);
        $this->assertSame('529.982.247-25', $resident->document);
        $this->assertSame('1990-03-15', $resident->birth_date?->toDateString());

        $this->assertDatabaseHas('addresses', [
            'id' => $point->address_id,
            'street' => 'Rua Goiás',
            'number' => '123',
            'neighborhood' => 'Centro',
            'reference' => 'Próximo à praça',
            'city_id' => $ctx['city']->id,
        ]);
        $this->assertDatabaseHas('sales', ['id' => $saleId, 'due_day' => 10]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_id' => $ctx['product']->id,
            'quantity' => 1,
        ]);

        $handoff = $response->json('data.office_handoff');
        $this->assertStringContainsString('Venda Expandor: #'.$saleId, $handoff['message']);
        $this->assertStringContainsString('João da Silva', $handoff['message']);
        $this->assertStringContainsString($ctx['seller']->name, $handoff['message']);
        $this->assertStringContainsString('Dia 10', $handoff['message']);
        $this->assertStringContainsString('Pendente', $handoff['message']);
        $this->assertStringContainsString('wa.me/5564999998888', (string) $handoff['whatsapp_url']);

        $this->assertSame(-16.7, (float) $point->fresh()->latitude);
        $this->assertSame(-51.2, (float) $point->fresh()->longitude);
    }

    public function test_invalid_due_day_returns_human_message(): void
    {
        $ctx = $this->opsContext();
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Cliente');

        $payload = $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
        ], $ctx);
        unset($payload['due_day']);
        $missing = $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $payload);
        $missing->assertStatus(422);
        $this->assertStringNotContainsString('due_day.required', (string) $missing->getContent());
        $this->assertStringContainsString('vencimento', mb_strtolower($missing->json('message')));

        $invalid = $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
            'due_day' => 7,
        ], $ctx));
        $invalid->assertStatus(422);
        $this->assertStringContainsString('5, 10, 15, 20, 25 ou 30', $invalid->json('message'));
    }

    public function test_valid_due_days_are_accepted(): void
    {
        $ctx = $this->opsContext();
        foreach ([5, 15, 20, 25, 30] as $i => $day) {
            $point = $this->makePoint($ctx, -16.71 - ($i * 0.001), -51.2, 'Cliente '.$day);
            $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
                'campaign_id' => $ctx['campaign']->id,
                'due_day' => $day,
                'customer_name' => 'Cliente '.$day,
            ], $ctx))->assertCreated()->assertJsonPath('data.office_handoff.due_day', $day);
        }
    }

    public function test_legacy_sale_without_complete_sale_still_allows_null_due_day(): void
    {
        $ctx = $this->opsContext();
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Legado');

        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'customer_name' => 'Legado',
            'customer_phone' => '11999990000',
            'items' => [['product_id' => $ctx['product']->id, 'quantity' => 1]],
        ])->assertCreated();
    }

    public function test_handoff_endpoint_copy_disabled_whatsapp_and_resend(): void
    {
        $ctx = $this->opsContext();
        app(OfficeSalesWhatsAppSettings::class)->save($ctx['seller']->company, '64999998888', true);
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Cliente');
        $created = $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
        ], $ctx))->assertCreated();
        $saleId = (int) $created->json('data.sale_id');
        $first = $created->json('data.office_handoff.message');

        $again = $this->mobileGet("/api/mobile/v1/sales/{$saleId}/handoff", $ctx['token'], $ctx['device'])
            ->assertOk()
            ->json('data');
        $this->assertSame($first, $again['message']);
        $this->assertSame($ctx['seller']->name, $again['seller_name']);

        $this->mobilePostJson("/api/mobile/v1/sales/{$saleId}/handoff/copied", $ctx['token'], $ctx['device'], [])
            ->assertOk();

        $this->mobileGet("/api/mobile/v1/points/{$point->id}", $ctx['token'], $ctx['device'])
            ->assertOk()
            ->assertJsonPath('data.last_sale_id', $saleId);

        app(OfficeSalesWhatsAppSettings::class)->save($ctx['seller']->company, '64999998888', false);
        $disabled = $this->mobileGet("/api/mobile/v1/sales/{$saleId}/handoff", $ctx['token'], $ctx['device'])->json('data');
        $this->assertFalse($disabled['whatsapp_enabled']);
        $this->assertFalse($disabled['can_send']);
        $this->assertTrue($disabled['whatsapp_configured']);
        $this->assertTrue($disabled['copy_available']);
        $this->assertTrue($disabled['view_available']);
        $this->assertNull($disabled['whatsapp_url']);
    }

    public function test_handoff_without_office_number_keeps_copy_and_explains_send(): void
    {
        $ctx = $this->opsContext();
        $ctx['seller']->company->update(['whatsapp' => null, 'phone' => null]);
        app(OfficeSalesWhatsAppSettings::class)->save($ctx['seller']->company->fresh(), '', true);
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Cliente');
        $created = $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
        ], $ctx))->assertCreated();
        $handoff = $created->json('data.office_handoff');
        $this->assertFalse($handoff['can_send']);
        $this->assertFalse($handoff['whatsapp_configured']);
        $this->assertTrue($handoff['copy_available']);
        $this->assertTrue($handoff['view_available']);
        $this->assertNull($handoff['whatsapp_url']);

        $boot = $this->mobileGet('/api/mobile/v1/bootstrap', $ctx['token'], $ctx['device'])->assertOk()->json('data.office_handoff');
        $this->assertFalse($boot['enabled']);
        $this->assertFalse($boot['configured']);
        $this->assertTrue($boot['whatsapp_enabled']);
    }

    public function test_visit_gps_does_not_overwrite_property_coordinates(): void
    {
        $ctx = $this->opsContext();
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Fixo');

        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
            'latitude' => -1.11,
            'longitude' => -2.22,
        ], $ctx))->assertCreated();

        $fresh = $point->fresh();
        $this->assertEqualsWithDelta(-16.7, (float) $fresh->latitude, 0.0001);
        $this->assertEqualsWithDelta(-51.2, (float) $fresh->longitude, 0.0001);
    }

    public function test_marker_preserved_after_sale_and_customers_refresh(): void
    {
        $ctx = $this->opsContext();
        $point = $this->makePoint($ctx, -16.7, -51.2, 'João da Silva');
        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
        ], $ctx))->assertCreated();

        $markers = $this->mobileGet('/api/mobile/v1/markers', $ctx['token'], $ctx['device'])->assertOk()->json('data.markers');
        $row = collect($markers)->first(fn ($m) => (int) $m['id'] === $point->id);
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(-16.7, (float) $row['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-51.2, (float) $row['longitude'], 0.0001);
        $this->assertSame(PropertyStatus::INSTALLATION_REQUESTED->value, $row['status']);

        $clients = $this->mobileGet('/api/mobile/v1/points?q=João', $ctx['token'], $ctx['device'])->json('data');
        $this->assertTrue(collect($clients)->contains(fn ($c) => (int) ($c['property_id'] ?? $c['id']) === $point->id));

        $this->mobileGet('/api/mobile/v1/results', $ctx['token'], $ctx['device'])->assertOk();
        $this->mobileGet('/api/mobile/v1/commissions', $ctx['token'], $ctx['device'])
            ->assertOk();
        $this->assertNotEmpty($this->mobileGet('/api/mobile/v1/commissions', $ctx['token'], $ctx['device'])->json('data.items'));
    }

    public function test_sale_does_not_create_follow_up_and_return_later_still_works(): void
    {
        $ctx = $this->opsContext();
        $sold = $this->makePoint($ctx, -16.7, -51.2, 'Venda');
        $this->mobilePostJson("/api/mobile/v1/points/{$sold->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
        ], $ctx))->assertCreated();

        $later = $this->makePoint($ctx, -16.71, -51.21, 'Retorno');
        $this->mobilePostJson("/api/mobile/v1/points/{$later->id}/visits", $ctx['token'], $ctx['device'], [
            'campaign_id' => $ctx['campaign']->id,
            'status' => VisitStatus::RETURN_LATER->value,
            'follow_up_at' => AppTime::now()->format('Y-m-d H:i:s'),
        ])->assertCreated();

        $agenda = $this->mobileGet('/api/mobile/v1/agenda?scope=today', $ctx['token'], $ctx['device'])->json('data');
        $ids = collect($agenda)->pluck('property_id')->all();
        $this->assertNotContains($sold->id, $ids);
        $this->assertContains($later->id, $ids);
    }

    public function test_campaign_active_required_and_tenant_isolation(): void
    {
        $ctx = $this->opsContext();
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Iso');
        $created = $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
        ], $ctx))->assertCreated();
        $saleId = (int) $created->json('data.sale_id');

        $other = $this->makeCompanyWithPlan('Outra Empresa Handoff');
        $otherSeller = $this->makeUser($other, Role::SELLER, ['email' => 'other-exp-handoff@test']);
        $device = $this->deviceId(88);
        $token = $this->mobileLogin($otherSeller, $device)->json('data.token');

        $handoffStatus = $this->mobileGet("/api/mobile/v1/sales/{$saleId}/handoff", $token, $device)->status();
        $this->assertContains($handoffStatus, [401, 403, 404]);

        $lonely = $this->makeUser($ctx['seller']->company, Role::SELLER, ['email' => 'no-campaign@test']);
        $lonelyDevice = $this->deviceId(89);
        $lonelyToken = $this->mobileLogin($lonely, $lonelyDevice)->json('data.token');
        $this->mobilePostJson('/api/mobile/v1/first-approach', $lonelyToken, $lonelyDevice, [
            'city_id' => $ctx['city']->id,
            'street' => 'Rua Sem Campanha',
            'latitude' => -16.7,
            'longitude' => -51.2,
            'status' => VisitStatus::INTERESTED->value,
        ])->assertStatus(422);
    }

    public function test_server_side_product_price_not_client_amount(): void
    {
        $ctx = $this->opsContext();
        $point = $this->makePoint($ctx, -16.7, -51.2, 'Preco');
        $this->mobilePostJson("/api/mobile/v1/points/{$point->id}/sales", $ctx['token'], $ctx['device'], $this->completeSalePayload([
            'campaign_id' => $ctx['campaign']->id,
            'negotiated_amount' => 1.23,
        ], $ctx))->assertCreated();

        $sale = Sale::query()->latest('id')->firstOrFail();
        $this->assertEquals((float) $ctx['product']->price, (float) $sale->items()->first()->unit_price);
    }

    public function test_shell_wires_complete_sale_handoff_and_duplicate_guard(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $cart = (string) file_get_contents(resource_path('js/mobile/sale-cart.js'));
        $api = (string) file_get_contents(resource_path('js/mobile/mobile-api.js'));
        $handoff = (string) file_get_contents(resource_path('js/mobile/sale-handoff.js'));

        $this->assertStringContainsString('sale-due-chips', $prepare);
        $this->assertStringContainsString('sale-success-sheet', $prepare);
        $this->assertStringContainsString('btn-success', $prepare);
        $this->assertStringContainsString('btn-info', $prepare);
        $this->assertStringContainsString('Enviar para o escritório', $prepare);
        $this->assertStringContainsString('sale-success-handoff-hint', $prepare);
        $this->assertStringContainsString('handoff-message-sheet', $prepare);
        $this->assertStringContainsString('point-handoff-block', $prepare);
        $this->assertStringContainsString('Dados do cliente', $prepare);
        $this->assertStringContainsString('Endereço da instalação', $prepare);
        $this->assertStringContainsString('complete_sale: true', $cart);
        $this->assertStringContainsString('saleHandoff', $api);
        $this->assertStringContainsString('copyHandoffMessage', $shell);
        $this->assertStringContainsString('showHandoffText', $shell);
        $this->assertStringContainsString('handleSaleSuccess', $shell);
        $this->assertStringContainsString('canSendOfficeHandoff', $shell);
        $this->assertStringContainsString('WhatsApp do escritório não configurado.', $shell);
        $this->assertStringContainsString("debugFlow('saleSuccess'", $shell);
        $this->assertStringContainsString('Confirmando', $shell);
        $this->assertStringContainsString('submitBtn.disabled = true', $shell);
        $this->assertStringContainsString('loadResults()', $shell);
        $this->assertStringContainsString('loadCommissions()', $shell);
        $this->assertStringContainsString('loadClients()', $shell);
        $this->assertStringContainsString('allowGps: status !== \'installation_requested\'', $shell);
        $this->assertStringContainsString('SALE_NETWORK_ERROR', $shell);
        $css = (string) file_get_contents(resource_path('css/exp-vendedor-shell.css'));
        $this->assertStringContainsString('.btn-success', $css);
        $this->assertStringContainsString('.btn-info', $css);
        $this->assertStringContainsString('min-height: 44px', $css);
        $this->assertStringContainsString('SaleHandoffFormatter', $handoff);
        $this->assertStringContainsString('Plugins?.Clipboard', $handoff);
        $this->assertFileExists(base_path('docs/sprint-exp-vendedor-complete-sale-handoff/AUDIT.md'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    private function completeSalePayload(array $overrides, array $ctx): array
    {
        return array_merge([
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'complete_sale' => true,
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
        ], $overrides);
    }

    /**
     * @return array{seller: User, token: string, device: string, city: City, sector: Sector, campaign: Campaign, product: Product}
     */
    private function opsContext(): array
    {
        $company = $this->makeCompanyWithPlan('Empresa EXP Venda Completa');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.exp.sale@test',
            'name' => 'Carlos Vendedor',
        ]);
        $device = $this->deviceId(41);
        $token = $this->mobileLogin($seller, $device)->json('data.token');
        $city = City::factory()->create(['company_id' => $company->id, 'name' => 'Bom Jardim de Goiás']);
        $sector = Sector::factory()->create(['company_id' => $company->id, 'city_id' => $city->id]);
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

        return compact('seller', 'token', 'device', 'city', 'sector', 'campaign', 'product');
    }

    private function makePoint(array $ctx, float $lat, float $lng, string $name): Property
    {
        $address = Address::factory()->create([
            'company_id' => $ctx['seller']->company_id,
            'city_id' => $ctx['city']->id,
            'sector_id' => $ctx['sector']->id,
            'street' => 'Rua Antiga',
            'number' => '1',
            'neighborhood' => 'Setor Oeste',
            'latitude' => $lat,
            'longitude' => $lng,
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
            'phone' => '64988880000',
            'is_primary_contact' => true,
        ]);

        return $property->fresh(['address', 'residents']);
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
