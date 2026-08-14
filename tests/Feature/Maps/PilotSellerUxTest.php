<?php

namespace Tests\Feature\Maps;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class PilotSellerUxTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_map_shows_day_brief_and_first_tips(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Piloto Seller');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-piloto@maps.test',
            'name' => 'Ana Vendedora',
        ]);

        $response = $this->actingAs($seller)->get(route('map.index'));

        $response->assertOk()
            ->assertSee('seller-day-brief')
            ->assertSee('Começar no mapa')
            ->assertSee('seller-tips-modal')
            ->assertSee('Minha localização')
            ->assertSee('Toque no mapa')
            ->assertSee('Continue na rua')
            ->assertSee('Pular')
            ->assertSee('visit-sale-finalize')
            ->assertSee('Confirmar venda')
            ->assertSee('point-notes')
            ->assertSee('Situação / interesse')
            ->assertSee('Casas visitadas hoje')
            ->assertDontSee('Visão da equipe')
            ->assertDontSee('Nenhuma residência nesta área')
            ->assertSee('data-map-operation-sheet="1"', false)
            ->assertSee('map-operation-body', false)
            ->assertSee('Salvar ponto', false)
            ->assertSee('Cancelar', false);
    }

    public function test_map_visit_contratou_requires_sale_fields_and_audits(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Piloto Contrato');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-contrato@maps.test',
        ]);

        app(TenantContext::class)->set($company, $seller);

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

        $product = \App\Domains\Sales\Products\Models\Product::factory()->create([
            'company_id' => $company->id,
            'name' => '200 Mega',
            'commission_amount' => 35,
        ]);

        $property = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::INTERESTED,
            'latitude' => -23.55,
            'longitude' => -46.63,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
                'sector_id' => $sector->id,
            ])->id,
        ]);

        $this->actingAs($seller)
            ->postJson(route('map.visits.store', $campaign), [
                'property_id' => $property->id,
                'status' => VisitStatus::INSTALLATION_REQUESTED->value,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'latitude' => -23.5501,
                'longitude' => -46.6301,
            ])
            ->assertStatus(422);

        $this->actingAs($seller)
            ->postJson(route('map.visits.store', $campaign), [
                'property_id' => $property->id,
                'status' => VisitStatus::INSTALLATION_REQUESTED->value,
                'customer_name' => 'João',
                'customer_phone' => '11999990000',
                'latitude' => -23.5501,
                'longitude' => -46.6301,
            ])
            ->assertStatus(422);

        $response = $this->actingAs($seller)
            ->postJson(route('map.visits.store', $campaign), [
                'property_id' => $property->id,
                'status' => VisitStatus::INSTALLATION_REQUESTED->value,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'customer_name' => 'João',
                'customer_phone' => '11999990000',
                'due_day' => 10,
                'notes' => 'Observação da visita',
                'sale_notes' => 'Observação da venda',
                'latitude' => -23.5501,
                'longitude' => -46.6301,
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Visita registrada')
            ->assertJsonPath('data.plan', '200 Mega');

        $visit = Visit::query()->where('property_id', $property->id)->firstOrFail();
        $this->assertSame('200 Mega', $visit->plan);
        $this->assertSame($product->id, (int) $visit->product_id);
        $this->assertSame(VisitStatus::INSTALLATION_REQUESTED, $visit->status);
        $this->assertEqualsWithDelta(-23.5501, (float) $visit->latitude, 0.0001);
        $this->assertEqualsWithDelta(-46.6301, (float) $visit->longitude, 0.0001);

        $this->assertDatabaseHas('sales', [
            'visit_id' => $visit->id,
            'notes' => 'Observação da venda',
        ]);
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'visit.registered',
            'user_id' => $seller->id,
            'company_id' => $company->id,
        ]);

        $audit = AuditLog::query()
            ->where('action', 'visit.registered')
            ->where('user_id', $seller->id)
            ->firstOrFail();

        $this->assertSame('200 Mega', $audit->new_values['plan'] ?? null);
        $this->assertEquals(-23.5501, (float) ($audit->new_values['latitude'] ?? 0));
    }

    public function test_map_point_create_audits_and_accepts_notes(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Piloto Ponto');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-ponto@maps.test',
        ]);

        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($seller)->postJson(route('map.points.store'), [
            'city_id' => $city->id,
            'street' => 'Local GPS',
            'latitude' => -23.551,
            'longitude' => -46.631,
            'status' => PropertyStatus::INTERESTED->value,
            'contact_name' => 'Maria',
            'contact_phone' => '11999990000',
            'notes' => 'Portão azul',
            'gps_accuracy' => 8,
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Ponto salvo');

        $property = Property::query()->where('notes', 'Portão azul')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'point.created',
            'user_id' => $seller->id,
            'auditable_id' => $property->id,
        ]);
    }
}
