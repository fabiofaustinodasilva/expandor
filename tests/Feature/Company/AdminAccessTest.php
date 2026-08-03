<?php

namespace Tests\Feature\Company;

use App\Domains\Company\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_administrator_can_access_company_settings(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Alpha');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@alpha.test',
        ]);

        $response = $this->actingAs($admin)->get(route('company.edit', $company));

        $response->assertOk()
            ->assertSee('Nome fantasia')
            ->assertSee('Empresa Alpha')
            ->assertSee('Segmento');
    }

    public function test_user_without_permission_cannot_access_company_settings(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Beta');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@beta.test',
        ]);

        $response = $this->actingAs($viewer)->get(route('company.edit', $company));

        $response->assertForbidden();
    }

    public function test_dashboard_shows_correct_company(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Gamma', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@gamma.test',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Empresa Gamma')
            ->assertSee('Enterprise')
            ->assertSee('active');
    }
}
