<?php

namespace Tests\Feature\Visits;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Support\VisitHistoryPresenter;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class VisitHistoryTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_sees_commercial_history_cards(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Histórico');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-history@test']);

        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'UNICA NA SUA PORTA',
        ]);
        $address = Address::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'street' => 'Rua das Flores',
            'number' => '100',
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'latitude' => -16.68,
            'longitude' => -49.25,
        ]);
        Resident::factory()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'João Silva',
            'phone' => '64999999999',
            'is_primary_contact' => true,
        ]);

        Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::INTERESTED,
            'visited_at' => now()->subHour(),
            'notes' => 'Pediu proposta',
        ]);

        $this->actingAs($seller)
            ->get(route('operations.my-visits'))
            ->assertOk()
            ->assertSee('Histórico de atendimentos')
            ->assertSee('Seus atendimentos e resultados comerciais.')
            ->assertSee('João Silva')
            ->assertSee('🔵 Interessado')
            ->assertSee('UNICA NA SUA PORTA')
            ->assertSee('64999999999')
            ->assertSee('WhatsApp')
            ->assertSee('Rota')
            ->assertSee('Sem próximo passo definido')
            ->assertDontSee('Local GPS');
    }

    public function test_local_gps_street_is_hidden_from_seller(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa GPS Label');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-gps@test']);

        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $address = Address::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'street' => 'Local GPS',
            'number' => null,
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
        ]);

        Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::RETURN_LATER,
            'visited_at' => now(),
        ]);

        $this->assertSame('Local GPS', $address->fresh()->street);

        $this->actingAs($seller)
            ->get(route('operations.my-visits'))
            ->assertOk()
            ->assertSee(VisitHistoryPresenter::GPS_FALLBACK_LABEL)
            ->assertSee('🟡 Retorno marcado')
            ->assertDontSee('>Local GPS<', false);
    }

    public function test_next_action_prefers_visit_follow_up_then_property_fallback(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Next Action');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-next@test']);

        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $older = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::RETURN_LATER,
            'visited_at' => now()->subDays(2),
        ]);
        $newer = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::INTERESTED,
            'visited_at' => now()->subHour(),
        ]);

        FollowUp::factory()->create([
            'company_id' => $company->id,
            'visit_id' => $older->id,
            'user_id' => $seller->id,
            'status' => FollowUpStatus::PENDING,
            'scheduled_at' => now()->addDays(3)->setTime(14, 30),
            'notes' => 'Retorno do ciclo',
        ]);

        $response = $this->actingAs($seller)
            ->get(route('operations.my-visits'))
            ->assertOk();

        // Visita mais recente (sem FU próprio) herda fallback da property
        $response->assertSee('às 14:30');
        $response->assertSee('Retornar');
    }

    public function test_commercial_labels_do_not_replace_formal_label(): void
    {
        $this->assertSame('Instalação solicitada', VisitStatus::INSTALLATION_REQUESTED->label());
        $this->assertSame('🟢 Contratou', VisitStatus::INSTALLATION_REQUESTED->commercialLabel());
    }

    public function test_details_modal_payload_includes_visit_data_and_actions(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Drawer Histórico');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-drawer@test']);

        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Campanha Drawer',
        ]);
        $address = Address::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'street' => 'Av. Brasil',
            'number' => '50',
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'latitude' => -16.6799,
            'longitude' => -49.2550,
        ]);
        Resident::factory()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'Maria Drawer',
            'phone' => '64988887777',
            'is_primary_contact' => true,
        ]);

        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED,
            'plan' => '500 Mega',
            'notes' => 'Fechou na hora',
            'visited_at' => now()->subMinutes(30),
        ]);

        FollowUp::factory()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'user_id' => $seller->id,
            'status' => FollowUpStatus::PENDING,
            'scheduled_at' => now()->addDays(2)->setTime(14, 30),
        ]);

        $html = $this->actingAs($seller)
            ->get(route('operations.my-visits'))
            ->assertOk()
            ->assertSee('history-detail-modal', false)
            ->assertSee('history-cards-data', false)
            ->assertSee('visit-history.js', false)
            ->assertSee('data-visit-id="'.$visit->id.'"', false)
            ->assertSee('Maria Drawer')
            ->assertSee('🟢 Venda realizada')
            ->assertSee('wa.me/5564988887777', false)
            ->assertSee('destination=-16.6799', false)
            ->assertSee('às 14:30')
            ->assertSee('500 Mega')
            ->assertSee('Fechou na hora')
            ->getContent();

        $this->assertStringContainsString('id="history-cards-data"', $html);
        $this->assertMatchesRegularExpression('/"id"\s*:\s*'.$visit->id.'/', $html);
        $this->assertStringContainsString('Maria Drawer', $html);
        $this->assertStringContainsString('new_follow_up_url', $html);
        $this->assertStringContainsString('map_url', $html);
        $this->assertStringContainsString('route_href', $html);
    }

    public function test_details_payload_uses_gps_fallback_without_client_name(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Drawer GPS');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-drawer-gps@test']);

        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $address = Address::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'street' => 'Local GPS',
            'number' => null,
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'latitude' => -16.70,
            'longitude' => -49.26,
        ]);

        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::NOT_HOME,
            'visited_at' => now(),
        ]);

        $this->actingAs($seller)
            ->get(route('operations.my-visits'))
            ->assertOk()
            ->assertSee(VisitHistoryPresenter::GPS_FALLBACK_LABEL)
            ->assertSee('data-visit-id="'.$visit->id.'"', false)
            ->assertSee('Sem próximo passo definido')
            ->assertDontSee('"client":"Local GPS"', false);
    }

    public function test_seller_does_not_see_other_sellers_visits(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Isolation History');
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'seller-a-hist@test']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'seller-b-hist@test']);

        app(TenantContext::class)->set($company, $sellerA);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $property = Property::factory()->create(['company_id' => $company->id]);

        Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $sellerB->id,
            'status' => VisitStatus::NO_INTEREST,
            'notes' => 'Nota exclusiva B',
            'visited_at' => now(),
        ]);

        $this->actingAs($sellerA)
            ->get(route('operations.my-visits'))
            ->assertOk()
            ->assertDontSee('Nota exclusiva B');
    }
}
