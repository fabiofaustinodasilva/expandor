<?php

namespace Tests\Feature\Integrations;

use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Role;
use Database\Seeders\EnsureIntegrationsPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint8221PlatformAdminDashboardRedirectTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_platform_admin_visiting_tenant_dashboard_is_redirected_not_403(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->assertFalse($owner->hasPermission('dashboard.view'));

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertRedirect(route('platform.dashboard'));
    }

    public function test_company_administrator_dashboard_ok(): void
    {
        $company = $this->makeCompanyWithPlan('Co Dash');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_ensure_seeder_repairs_detached_dashboard_view(): void
    {
        $company = $this->makeCompanyWithPlan('Repair Co');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);

        $role = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();
        $dashboardId = Permission::query()->where('slug', 'dashboard.view')->value('id');
        $role->permissions()->detach($dashboardId);

        $admin->unsetRelation('role');
        $this->assertFalse($admin->fresh()->load('role.permissions')->hasPermission('dashboard.view'));

        $this->seed(EnsureIntegrationsPermissionsSeeder::class);

        $admin->unsetRelation('role');
        $this->assertTrue($admin->fresh()->load('role.permissions')->hasPermission('dashboard.view'));

        $this->actingAs($admin->fresh())
            ->get(route('dashboard'))
            ->assertOk();
    }
}
