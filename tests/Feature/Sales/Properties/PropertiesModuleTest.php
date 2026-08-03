<?php

namespace Tests\Feature\Sales\Properties;

use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Models\PropertyHistory;
use App\Domains\Sales\Properties\Services\PropertyService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class PropertiesModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_a_cannot_access_properties_from_company_b(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Props A');
        $companyB = $this->makeCompanyWithPlan('Empresa Props B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@props.test',
        ]);

        $addressA = Address::factory()->create([
            'company_id' => $companyA->id,
            'street' => 'Rua Exclusiva Alpha',
            'number' => '100',
        ]);

        $addressB = Address::factory()->create([
            'company_id' => $companyB->id,
            'street' => 'Rua Exclusiva Beta',
            'number' => '200',
        ]);

        Property::factory()->create([
            'company_id' => $companyA->id,
            'address_id' => $addressA->id,
        ]);

        Property::factory()->create([
            'company_id' => $companyB->id,
            'address_id' => $addressB->id,
        ]);

        $response = $this->actingAs($adminA)->get(route('properties.index'));

        $response->assertOk()
            ->assertSee('Rua Exclusiva Alpha')
            ->assertDontSee('Rua Exclusiva Beta');
    }

    public function test_status_change_creates_history(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Histórico');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-history@props.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $property = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NEW,
        ]);

        $response = $this->actingAs($admin)->put(route('properties.status.update', $property), [
            'status' => PropertyStatus::INTERESTED->value,
            'description' => 'Cliente pediu retorno.',
        ]);

        $response->assertRedirect(route('properties.index'));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => PropertyStatus::INTERESTED->value,
        ]);

        $this->assertDatabaseHas('property_histories', [
            'property_id' => $property->id,
            'user_id' => $admin->id,
            'old_status' => PropertyStatus::NEW->value,
            'new_status' => PropertyStatus::INTERESTED->value,
            'description' => 'Cliente pediu retorno.',
        ]);

        $this->assertSame(1, PropertyHistory::query()->where('property_id', $property->id)->count());
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Viewer Props');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@props.test',
        ]);

        $response = $this->actingAs($viewer)->get(route('properties.index'));

        $response->assertForbidden();
    }

    public function test_creating_property_records_initial_history(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Create Props');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-create@props.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $address = Address::factory()->create([
            'company_id' => $company->id,
        ]);

        $property = app(PropertyService::class)->createProperty([
            'address_id' => $address->id,
            'type' => 'house',
            'status' => PropertyStatus::NEW->value,
            'notes' => 'Primeiro cadastro',
        ], $admin);

        $this->assertDatabaseHas('property_histories', [
            'property_id' => $property->id,
            'user_id' => $admin->id,
            'new_status' => PropertyStatus::NEW->value,
        ]);
    }
}
