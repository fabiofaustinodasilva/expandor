<?php

namespace Tests\Feature\Sales\Residents;

use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Enums\ResidentHistoryEvent;
use App\Domains\Sales\Residents\Enums\ResidentStatus;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Residents\Models\ResidentHistory;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class ResidentsModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_a_cannot_access_residents_from_company_b(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Residents A');
        $companyB = $this->makeCompanyWithPlan('Empresa Residents B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@residents.test',
        ]);

        $propertyA = Property::factory()->create([
            'company_id' => $companyA->id,
        ]);

        $propertyB = Property::factory()->create([
            'company_id' => $companyB->id,
        ]);

        Resident::factory()->create([
            'company_id' => $companyA->id,
            'property_id' => $propertyA->id,
            'name' => 'Morador Alpha Unico',
        ]);

        Resident::factory()->create([
            'company_id' => $companyB->id,
            'property_id' => $propertyB->id,
            'name' => 'Morador Beta Unico',
        ]);

        $listResponse = $this->actingAs($adminA)->get(route('properties.residents.index', $propertyA));

        $listResponse->assertOk()
            ->assertSee('Morador Alpha Unico')
            ->assertDontSee('Morador Beta Unico');

        $foreignPropertyResponse = $this->actingAs($adminA)->get(route('properties.residents.index', $propertyB));
        $foreignPropertyResponse->assertNotFound();

        $foreignResident = Resident::withoutGlobalScopes()
            ->where('company_id', $companyB->id)
            ->firstOrFail();

        $foreignEditResponse = $this->actingAs($adminA)->get(route('residents.edit', $foreignResident));
        $foreignEditResponse->assertNotFound();
    }

    public function test_resident_belongs_to_correct_property(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Resident Property');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-belong@residents.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $property = Property::factory()->create([
            'company_id' => $company->id,
        ]);

        $otherProperty = Property::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($admin)->post(route('properties.residents.store', $property), [
            'name' => 'Maria Silva',
            'phone' => '(11) 99999-0000',
            'email' => 'maria@example.test',
            'document' => null,
            'is_primary_contact' => true,
            'status' => ResidentStatus::ACTIVE->value,
            'notes' => 'Contato principal',
        ]);

        $response->assertRedirect(route('properties.residents.index', $property));

        $this->assertDatabaseHas('residents', [
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'Maria Silva',
            'status' => ResidentStatus::ACTIVE->value,
            'is_primary_contact' => true,
        ]);

        $this->assertDatabaseMissing('residents', [
            'property_id' => $otherProperty->id,
            'name' => 'Maria Silva',
        ]);

        $resident = Resident::query()->where('name', 'Maria Silva')->firstOrFail();
        $this->assertSame($property->id, $resident->property_id);
        $this->assertTrue($resident->property->is($property));
    }

    public function test_status_change_creates_history(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Resident History');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-history@residents.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $property = Property::factory()->create([
            'company_id' => $company->id,
        ]);

        $resident = Resident::factory()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'status' => ResidentStatus::ACTIVE,
        ]);

        $response = $this->actingAs($admin)->put(route('residents.status.update', $resident), [
            'status' => ResidentStatus::MOVED->value,
            'description' => 'Família mudou de endereço.',
        ]);

        $response->assertRedirect(route('properties.residents.index', $property));

        $this->assertDatabaseHas('residents', [
            'id' => $resident->id,
            'status' => ResidentStatus::MOVED->value,
        ]);

        $this->assertDatabaseHas('resident_histories', [
            'company_id' => $company->id,
            'resident_id' => $resident->id,
            'property_id' => $property->id,
            'event' => ResidentHistoryEvent::STATUS_CHANGED->value,
            'description' => 'Família mudou de endereço.',
        ]);

        $this->assertSame(
            1,
            ResidentHistory::query()
                ->where('resident_id', $resident->id)
                ->where('event', ResidentHistoryEvent::STATUS_CHANGED->value)
                ->count()
        );
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Viewer Residents');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@residents.test',
        ]);

        $property = Property::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($viewer)->get(route('properties.residents.index', $property));

        $response->assertForbidden();
    }
}
