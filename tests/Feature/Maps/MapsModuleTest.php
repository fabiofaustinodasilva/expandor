<?php

namespace Tests\Feature\Maps;

use App\Domains\Company\Models\Role;
use App\Domains\Maps\Enums\MapMarkerColor;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class MapsModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_authorized_user_can_access_map_page(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Maps Web');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-web@maps.test',
        ]);

        $response = $this->actingAs($admin)->get(route('map.index'));

        $response->assertOk()
            ->assertSee('Mapa operacional')
            ->assertSee('operational-map')
            ->assertSee('/api/v1/maps/markers')
            ->assertSee('map-provider')
            ->assertSee('commercial-filters')
            ->assertSee('Meu Local')
            ->assertDontSee('btn-next-house');
    }

    public function test_manager_map_shows_team_view_panel(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Maps Team');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-team@maps.test',
        ]);

        $response = $this->actingAs($admin)->get(route('map.index'));

        $response->assertOk()
            ->assertSee('Visão da equipe')
            ->assertSee('team-view-panel')
            ->assertSee('Região atual');
    }

    public function test_seller_map_hides_team_view_panel(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Maps Seller Field');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-field@maps.test',
        ]);

        $response = $this->actingAs($seller)->get(route('map.index'));

        $response->assertOk()
            ->assertSee('Meu Local')
            ->assertSee('Começar no mapa')
            ->assertSee('Casas visitadas hoje')
            ->assertDontSee('Próxima casa')
            ->assertDontSee('btn-next-house')
            ->assertSee('seller-day-brief')
            ->assertSee('data-is-field-seller="1"', false)
            ->assertSee('Situação')
            ->assertDontSee('id="seller-map-tools"')
            ->assertDontSee('id="toggle-layers"')
            ->assertDontSee('id="toggle-filters"')
            ->assertDontSee('id="toggle-legend"')
            ->assertDontSee('Visão da equipe')
            ->assertDontSee('team-view-panel')
            ->assertDontSee('toggle-filters-manager');
    }

    public function test_manager_map_keeps_full_chrome(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Maps Manager Full');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-full@maps.test',
        ]);

        $response = $this->actingAs($admin)->get(route('map.index'));

        $response->assertOk()
            ->assertSee('commercial-filters')
            ->assertSee('map-legend-panel')
            ->assertSee('Visão da equipe')
            ->assertSee('Região atual')
            ->assertSee('toggle-filters-manager')
            ->assertDontSee('id="seller-map-tools"');
    }

    public function test_user_without_permission_receives_forbidden_on_map_page(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Maps Viewer Web');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer-web@maps.test',
        ]);

        $response = $this->actingAs($viewer)->get(route('map.index'));

        $response->assertForbidden();
    }

    public function test_company_a_does_not_return_points_from_company_b(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Maps A');
        $companyB = $this->makeCompanyWithPlan('Empresa Maps B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@maps.test',
        ]);

        $propertyA = Property::factory()->create([
            'company_id' => $companyA->id,
            'latitude' => -23.5505200,
            'longitude' => -46.6333080,
            'status' => PropertyStatus::NEW,
        ]);

        Resident::factory()->create([
            'company_id' => $companyA->id,
            'property_id' => $propertyA->id,
            'name' => 'Morador Alpha Maps',
            'phone' => '(11) 98888-1111',
            'is_primary_contact' => true,
        ]);

        Property::factory()->create([
            'company_id' => $companyB->id,
            'latitude' => -22.9068470,
            'longitude' => -43.1728970,
            'status' => PropertyStatus::CUSTOMER,
        ]);

        Sanctum::actingAs($adminA);

        $response = $this->getJson('/api/v1/maps/markers');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $markers = collect($response->json('data.markers'));

        $this->assertCount(1, $markers);
        $this->assertSame($propertyA->id, $markers->first()['property_id']);
        $this->assertSame('Morador Alpha Maps', $markers->first()['resident_name']);
        $this->assertSame('(11) 98888-1111', $markers->first()['resident_phone']);
        $this->assertArrayHasKey('updated_at', $markers->first());
        $this->assertFalse($markers->contains(fn (array $marker) => abs($marker['latitude'] - (-22.9068470)) < 0.0001));
    }

    public function test_filter_by_status_works(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Maps Status');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-status@maps.test',
        ]);

        $interested = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::INTERESTED,
            'latitude' => -23.5500000,
            'longitude' => -46.6300000,
        ]);

        Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NO_INTEREST,
            'latitude' => -23.5600000,
            'longitude' => -46.6400000,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/maps/markers?property_status='.PropertyStatus::INTERESTED->value);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $markers = collect($response->json('data.markers'));

        $this->assertCount(1, $markers);
        $this->assertSame($interested->id, $markers->first()['property_id']);
        $this->assertSame(PropertyStatus::INTERESTED->value, $markers->first()['status']);
        $this->assertSame(MapMarkerColor::BLUE->value, $markers->first()['color']);
        $this->assertSame('interested', $markers->first()['commercial_group']);
        $this->assertArrayHasKey('summary', $response->json('data'));
        $this->assertSame(1, $response->json('data.summary.interested'));
    }

    public function test_commercial_group_filter_and_summary(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Maps Commercial');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-commercial@maps.test',
        ]);

        Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::CUSTOMER,
            'latitude' => -23.5500000,
            'longitude' => -46.6300000,
            'created_by' => $admin->id,
        ]);
        Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NEW,
            'latitude' => -23.5600000,
            'longitude' => -46.6400000,
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/maps/markers?commercial_groups=customer');

        $response->assertOk();
        $markers = collect($response->json('data.markers'));
        $this->assertCount(1, $markers);
        $this->assertSame('customer', $markers->first()['commercial_group']);
        $this->assertSame(MapMarkerColor::GREEN->value, $markers->first()['color']);
        $this->assertSame(1, $response->json('data.summary.customer'));
        $this->assertSame(0, $response->json('data.summary.new'));
    }

    public function test_unauthorized_user_cannot_access_markers(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Maps Viewer');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@maps.test',
        ]);

        Property::factory()->create([
            'company_id' => $company->id,
            'latitude' => -23.5505200,
            'longitude' => -46.6333080,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/maps/markers');

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Access denied.');
    }
}
