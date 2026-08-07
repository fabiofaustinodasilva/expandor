<?php

namespace Tests\Feature\Maps;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Models\PropertyHistory;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Actions\RegisterFirstApproachAction;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class FirstApproachTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_first_approach_creates_property_and_visit_atomically(): void
    {
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa FA Base');

        $response = $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Local GPS',
            'latitude' => -23.551,
            'longitude' => -46.631,
            'status' => VisitStatus::INTERESTED->value,
            'campaign_id' => $campaign->id,
            'contact_name' => 'Maria',
            'notes' => 'Cliente demonstrou interesse no plano 500mb',
            'gps_accuracy' => 12,
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Atendimento registrado')
            ->assertJsonPath('data.first_approach', true)
            ->assertJsonPath('data.visit_status', VisitStatus::INTERESTED->value)
            ->assertJsonPath('data.status', PropertyStatus::INTERESTED->value);

        $property = Property::query()->findOrFail($response->json('data.property_id'));
        $visit = Visit::query()->where('property_id', $property->id)->firstOrFail();

        $this->assertSame($seller->id, (int) $property->created_by);
        $this->assertSame($campaign->id, (int) $visit->campaign_id);
        $this->assertSame($seller->id, (int) $visit->user_id);
        $this->assertTrue(
            PropertyHistory::query()
                ->where('property_id', $property->id)
                ->where('description', 'like', '%Primeiro atendimento realizado%')
                ->exists()
        );
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'point.created',
            'auditable_id' => $property->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'visit.registered',
            'auditable_id' => $visit->id,
        ]);
    }

    public function test_contratou_requires_sale_fields_and_creates_sale(): void
    {
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa FA Contrato');

        app(TenantContext::class)->set($seller->company, $seller);
        $product = \App\Domains\Sales\Products\Models\Product::factory()->create([
            'company_id' => $seller->company_id,
            'name' => '500 Mega',
            'commission_amount' => 50,
        ]);

        $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua A',
            'latitude' => -23.55,
            'longitude' => -46.63,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'campaign_id' => $campaign->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua A',
            'latitude' => -23.55,
            'longitude' => -46.63,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'campaign_id' => $campaign->id,
            'customer_name' => 'Maria Silva',
            'customer_phone' => '11988887777',
        ])->assertStatus(422);

        $ok = $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua A',
            'latitude' => -23.55,
            'longitude' => -46.63,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'campaign_id' => $campaign->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_name' => 'Maria Silva',
            'customer_phone' => '11988887777',
            'notes' => 'Observação da visita',
            'sale_notes' => 'Observação da venda',
        ]);

        $ok->assertCreated()
            ->assertJsonPath('data.status', PropertyStatus::INSTALLATION_REQUESTED->value)
            ->assertJsonPath('data.plan', '500 Mega');

        $visitId = Visit::query()->latest('id')->value('id');
        $this->assertDatabaseHas('sales_commissions', [
            'visit_id' => $visitId,
            'product_name' => '500 Mega',
            'commission_amount' => 50,
        ]);
        $this->assertDatabaseHas('sales', [
            'visit_id' => $visitId,
            'notes' => 'Observação da venda',
        ]);
        $this->assertDatabaseHas('sale_items', [
            'product_name' => '500 Mega',
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('residents', [
            'name' => 'Maria Silva',
            'phone' => '11988887777',
            'is_primary_contact' => 1,
        ]);
    }

    public function test_blocks_when_seller_has_no_active_campaign(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa FA Sem Campanha');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-nocamp@fa.test']);
        app(TenantContext::class)->set($company, $seller);
        $city = City::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Local GPS',
            'latitude' => -23.55,
            'longitude' => -46.63,
            'status' => VisitStatus::NO_INTEREST->value,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['campaign_id']);

        $message = $response->json('errors.campaign_id');
        $text = is_array($message) ? implode(' ', $message) : (string) $message;
        $this->assertStringContainsString('campanha ativa', $text);
    }

    public function test_auto_selects_single_active_campaign(): void
    {
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa FA Auto');

        $response = $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Local GPS',
            'latitude' => -23.55,
            'longitude' => -46.63,
            'status' => VisitStatus::NOT_HOME->value,
            // campaign_id omitido
        ]);

        $response->assertCreated();
        $visit = Visit::query()->findOrFail($response->json('data.visit_id'));
        $this->assertSame($campaign->id, (int) $visit->campaign_id);
        $this->assertSame(PropertyStatus::NEW, Property::query()->findOrFail($response->json('data.property_id'))->status);
    }

    public function test_return_later_can_schedule_follow_up(): void
    {
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa FA Retorno');

        $when = now()->addDay()->toDateString();

        $response = $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua Retorno',
            'latitude' => -23.56,
            'longitude' => -46.64,
            'status' => VisitStatus::RETURN_LATER->value,
            'campaign_id' => $campaign->id,
            'notes' => 'Voltar à tarde',
            'follow_up_at' => $when,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', PropertyStatus::RETURN_LATER->value);

        $visitId = $response->json('data.visit_id');
        $this->assertTrue(FollowUp::query()->where('visit_id', $visitId)->exists());
    }

    public function test_map_page_exposes_first_approach_contract_for_seller(): void
    {
        [$seller] = $this->sellerWithCampaign('Empresa FA UI');

        $this->actingAs($seller)->get(route('map.index'))
            ->assertOk()
            ->assertSee('/map/first-approach', false)
            ->assertSee('data-first-approach-url', false)
            ->assertSee('point-first-approach')
            ->assertSee('Resultado do atendimento')
            ->assertSee('Registre o resultado')
            ->assertSee('Meu Local')
            ->assertDontSee('btn-next-house');
    }

    public function test_action_message_constant_is_stable_for_clients(): void
    {
        $this->assertNotEmpty(RegisterFirstApproachAction::NO_CAMPAIGN_MESSAGE);
    }

    /**
     * @return array{0: \App\Domains\Company\Models\User, 1: City, 2: Campaign}
     */
    protected function sellerWithCampaign(string $companyName): array
    {
        $company = $this->makeCompanyWithPlan($companyName);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-'.uniqid().'@fa.test',
        ]);
        app(TenantContext::class)->set($company, $seller);
        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->attach($seller->id);

        return [$seller, $city, $campaign];
    }
}
