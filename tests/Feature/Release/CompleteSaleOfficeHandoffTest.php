<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Handoff\OfficeSalesWhatsAppSettings;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\VisitStatus;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class CompleteSaleOfficeHandoffTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_complete_sale_persists_client_address_due_day_and_handoff(): void
    {
        [$seller, $campaign, $property, $product, $city] = $this->seedSaleContext();
        app(OfficeSalesWhatsAppSettings::class)->save($seller->company, '64999998888', true);

        $response = $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), [
            'property_id' => $property->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_name' => 'João da Silva',
            'customer_document' => '529.982.247-25',
            'customer_birth_date' => '15/03/1990',
            'customer_phone' => '(64) 99999-9999',
            'install_street' => 'Rua Goiás',
            'install_number' => '123',
            'install_neighborhood' => 'Centro',
            'install_reference' => 'Próximo à praça',
            'install_city' => $city->name,
            'due_day' => 10,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.office_handoff.seller_name', $seller->name)
            ->assertJsonPath('data.office_handoff.whatsapp_enabled', true);

        $saleId = (int) $response->json('data.office_handoff.sale_id');
        $this->assertSame($saleId, (int) $response->json('data.commission_awarded.sale_id'));

        $resident = \App\Domains\Sales\Residents\Models\Resident::query()
            ->where('property_id', $property->id)
            ->where('name', 'João da Silva')
            ->firstOrFail();
        $this->assertSame('(64) 99999-9999', $resident->phone);
        $this->assertSame('529.982.247-25', $resident->document);
        $this->assertSame('1990-03-15', $resident->birth_date?->toDateString());
        $this->assertDatabaseHas('addresses', [
            'id' => $property->address_id,
            'street' => 'Rua Goiás',
            'number' => '123',
            'neighborhood' => 'Centro',
            'reference' => 'Próximo à praça',
            'city_id' => $city->id,
        ]);
        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'due_day' => 10,
        ]);

        $handoff = $response->json('data.office_handoff');
        $this->assertStringContainsString('Venda Expandor: #'.$saleId, $handoff['message']);
        $this->assertStringContainsString('João da Silva', $handoff['message']);
        $this->assertStringContainsString($seller->name, $handoff['message']);
        $this->assertStringContainsString('R$ 50,00', $handoff['message']);
        $this->assertStringContainsString('Pendente', $handoff['message']);
        $this->assertStringContainsString('Dia 10', $handoff['message']);
        $this->assertStringContainsString('https://maps.google.com/?q=', $handoff['maps_url']);
        $this->assertStringContainsString('-16.7', $handoff['maps_url']);
        $this->assertStringNotContainsString('null', $handoff['message']);
        $this->assertStringContainsString('wa.me/5564999998888', (string) $handoff['whatsapp_url']);
        $this->assertStringContainsString($seller->name, $handoff['message']);

        $this->assertDatabaseHas('sales_commissions', [
            'visit_id' => $response->json('data.visit_id'),
            'commission_amount' => 50,
        ]);
    }

    public function test_allowed_due_days_and_invalid_rejected(): void
    {
        [$seller, $campaign, $property, $product] = $this->seedSaleContext();

        foreach ([5, 10, 15, 20, 25, 30] as $day) {
            $prop = $this->cloneProperty($property);
            $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), $this->salePayload($prop, $product, [
                'due_day' => $day,
                'customer_name' => 'Cliente Dia '.$day,
            ]))->assertCreated();
            $this->assertDatabaseHas('sales', ['due_day' => $day]);
        }

        $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), $this->salePayload($property, $product, [
            'due_day' => 12,
            'customer_name' => 'Cliente Invalido',
        ]))->assertStatus(422)->assertJsonValidationErrors('due_day');

        $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), $this->salePayload($this->cloneProperty($property), $product, [
            'due_day' => null,
            'customer_name' => 'Sem Vencimento',
        ]))->assertStatus(422)->assertJsonValidationErrors('due_day');

        $missing = $this->salePayload($this->cloneProperty($property), $product, [
            'customer_name' => 'Sem Campo Due Day',
        ]);
        unset($missing['due_day']);
        $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), $missing)
            ->assertStatus(422)
            ->assertJsonValidationErrors('due_day');
    }

    public function test_products_and_prices_come_from_backend_catalog(): void
    {
        [$seller, $campaign, $property, $product] = $this->seedSaleContext();
        $this->actingAs($seller)->get(route('map.index'))
            ->assertOk()
            ->assertSee($product->name, false)
            ->assertSee('CONFIRMAR VENDA');

        $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), $this->salePayload($property, $product, [
            'due_day' => 5,
        ]))->assertCreated();

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'unit_price' => $product->price,
            'product_name' => $product->name,
        ]);
    }

    public function test_handoff_regenerates_and_copy_does_not_change_sale(): void
    {
        [$seller, $campaign, $property, $product] = $this->seedSaleContext();
        app(OfficeSalesWhatsAppSettings::class)->save($seller->company, '64911112222', true);

        $created = $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), $this->salePayload($property, $product, [
            'due_day' => 20,
            'install_reference' => '',
        ]))->assertCreated();

        $saleId = (int) $created->json('data.office_handoff.sale_id');
        $first = $created->json('data.office_handoff.message');
        $this->assertStringNotContainsString('Referência: null', $first);

        $again = $this->actingAs($seller)->getJson(route('sales.handoff.show', $saleId))
            ->assertOk()
            ->json('data.message');
        $this->assertSame($first, $again);

        $this->actingAs($seller)->postJson(route('sales.handoff.copied', $saleId))
            ->assertOk()
            ->assertJsonPath('message', 'Mensagem copiada.');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sale.office_handoff_copied',
            'auditable_id' => $saleId,
        ]);
        $this->assertDatabaseHas('sales', ['id' => $saleId, 'due_day' => 20]);

        $this->actingAs($seller)->get(route('visits.show', $created->json('data.visit_id')))
            ->assertOk()
            ->assertSee('Encaminhamento ao escritório')
            ->assertSee('Copiar mensagem');
    }

    public function test_whatsapp_disabled_hides_send_action_and_seller_cannot_override_destination(): void
    {
        [$seller, $campaign, $property, $product] = $this->seedSaleContext();
        app(OfficeSalesWhatsAppSettings::class)->save($seller->company, '64999990000', false);

        $response = $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), $this->salePayload($property, $product, [
            'due_day' => 25,
        ]))->assertCreated();

        $this->assertFalse($response->json('data.office_handoff.whatsapp_enabled'));
        $this->assertNull($response->json('data.office_handoff.whatsapp_url'));

        $saleId = $response->json('data.office_handoff.sale_id');
        $this->actingAs($seller)->get(route('visits.show', $response->json('data.visit_id')))
            ->assertOk()
            ->assertDontSee('Enviar no WhatsApp');

        $payload = $this->actingAs($seller)->getJson(route('sales.handoff.show', $saleId))->json('data');
        $this->assertSame($seller->name, $payload['seller_name']);
        $this->assertStringNotContainsString('wa.me/5511999999999', (string) $payload['whatsapp_url']);
    }

    public function test_tenant_isolation_on_handoff(): void
    {
        [$seller, $campaign, $property, $product] = $this->seedSaleContext();
        $created = $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), $this->salePayload($property, $product, [
            'due_day' => 30,
        ]))->assertCreated();
        $saleId = $created->json('data.office_handoff.sale_id');

        $other = $this->makeCompanyWithPlan('Outra Empresa Handoff');
        $otherSeller = $this->makeUser($other, Role::SELLER, ['email' => 'other-handoff@test']);

        $this->actingAs($otherSeller)
            ->getJson(route('sales.handoff.show', $saleId))
            ->assertNotFound();
    }

    public function test_map_page_exposes_complete_sale_ui(): void
    {
        [$seller] = $this->seedSaleContext();
        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringContainsString('CONFIRMAR VENDA', $html);
        $this->assertStringContainsString('due-day-chip', $html);
        $this->assertStringContainsString('sale-handoff-modal', $html);
        $this->assertStringContainsString('operational-map.js?v=62', $html);
        $this->assertStringContainsString('Dia de vencimento', $html);
    }

    public function test_sale_settings_persist_office_whatsapp(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Config WA');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-wa@test']);
        app(TenantContext::class)->set($company, $admin);

        $this->actingAs($admin)->put(route('operations.settings.sale.update'), [
            'required_fields' => ['name', 'phone', 'product'],
            'office_sales_whatsapp' => '(64) 98888-7777',
            'office_sales_whatsapp_enabled' => '1',
        ])->assertRedirect(route('operations.settings.sale'));

        $resolved = app(OfficeSalesWhatsAppSettings::class)->forCompany($company->fresh());
        $this->assertTrue($resolved['enabled_flag']);
        $this->assertSame('(64) 98888-7777', $resolved['number']);
    }

    /**
     * @return array{0: mixed, 1: Campaign, 2: Property, 3: Product, 4: City}
     */
    private function seedSaleContext(): array
    {
        $company = $this->makeCompanyWithPlan('Empresa Handoff Venda');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-handoff@test',
            'name' => 'Carlos Vendedor',
        ]);
        app(TenantContext::class)->set($company, $seller);
        $city = City::factory()->create(['company_id' => $company->id, 'name' => 'Bom Jardim de Goiás']);
        $sector = Sector::factory()->create(['company_id' => $company->id, 'city_id' => $city->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->sync([$seller->id]);
        $address = Address::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'sector_id' => $sector->id,
            'street' => 'Rua Antiga',
            'number' => '1',
            'neighborhood' => 'Setor Oeste',
            'latitude' => -16.7,
            'longitude' => -51.2,
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'latitude' => -16.7,
            'longitude' => -51.2,
            'created_by' => $seller->id,
        ]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => '500 Mega',
            'price' => 97.90,
            'commission_amount' => 50,
        ]);

        return [$seller, $campaign, $property, $product, $city];
    }

    private function cloneProperty(Property $source): Property
    {
        $address = Address::factory()->create([
            'company_id' => $source->company_id,
            'city_id' => $source->address()->value('city_id'),
            'street' => 'Rua Clone',
            'number' => '9',
            'neighborhood' => 'Centro',
        ]);

        return Property::factory()->create([
            'company_id' => $source->company_id,
            'address_id' => $address->id,
            'latitude' => $source->latitude,
            'longitude' => $source->longitude,
            'created_by' => $source->created_by,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function salePayload(Property $property, Product $product, array $overrides = []): array
    {
        return array_merge([
            'property_id' => $property->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_name' => 'Cliente Teste',
            'customer_phone' => '64988887777',
            'due_day' => 15,
        ], $overrides);
    }
}
