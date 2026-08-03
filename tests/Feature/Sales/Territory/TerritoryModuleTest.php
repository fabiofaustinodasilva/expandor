<?php

namespace Tests\Feature\Sales\Territory;

use App\Domains\Company\Models\Role;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class TerritoryModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_a_cannot_view_cities_from_company_b(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa A');
        $companyB = $this->makeCompanyWithPlan('Empresa B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@territory.test',
        ]);

        City::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Cidade Alpha',
            'state' => 'GO',
        ]);

        City::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Cidade Beta',
            'state' => 'SP',
        ]);

        $response = $this->actingAs($adminA)->get(route('cities.index'));

        $response->assertOk()
            ->assertSee('Cidade Alpha')
            ->assertDontSee('Cidade Beta');
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Viewer');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@territory.test',
        ]);

        $response = $this->actingAs($viewer)->get(route('cities.index'));

        $response->assertForbidden();
    }

    public function test_sector_belongs_to_correct_city(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Setor');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@territory.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $city = City::factory()->create([
            'company_id' => $company->id,
            'name' => 'Goiânia',
            'state' => 'GO',
        ]);

        $otherCity = City::factory()->create([
            'company_id' => $company->id,
            'name' => 'Anápolis',
            'state' => 'GO',
        ]);

        $sector = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Setor Bueno',
        ]);

        $this->assertTrue($sector->city->is($city));
        $this->assertFalse($sector->city->is($otherCity));
        $this->assertSame('Goiânia', $sector->city->name);

        $response = $this->actingAs($admin)->get(route('sectors.index'));

        $response->assertOk()
            ->assertSee('Setor Bueno')
            ->assertSee('Goiânia');
    }
}
