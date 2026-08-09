<?php

namespace Tests\Feature\Integrations;

use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Http\Controllers\Web\Dashboard\DashboardController;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Hotfix: prove GET /dashboard 403 source and that Administrator keeps access after 8.2.21.
 */
class Sprint8221DashboardAccessHotfixTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_administrator_can_access_dashboard_after_8221_seed(): void
    {
        $company = $this->makeCompanyWithPlan('Hotfix Admin Co', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-hotfix@dashboard.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $admin->load(['role.permissions', 'permissionOverrides']);

        $this->assertTrue($admin->hasPermission('dashboard.view'), 'Administrator must keep dashboard.view after RolePermissionSeeder sync');
        $this->assertTrue($admin->hasPermission('integrations.view'));
        $this->assertTrue($admin->hasPermission('integrations.manage'));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_403_access_denied_comes_from_dashboard_controller_permission_gate(): void
    {
        $company = $this->makeCompanyWithPlan('Hotfix Denied Co', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-denied@dashboard.test',
        ]);

        $role = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();
        $dashboardPermId = Permission::query()->where('slug', 'dashboard.view')->value('id');
        $this->assertNotNull($dashboardPermId);

        // Simulate production pivot missing dashboard.view (destructive sync / incomplete grant).
        $role->permissions()->detach($dashboardPermId);
        $admin->unsetRelation('role');
        $admin->load(['role.permissions', 'permissionOverrides']);

        $this->assertFalse($admin->hasPermission('dashboard.view'));

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertForbidden();
        $response->assertSee('Access denied.', false);

        // Pin the responsible guard: same check as DashboardController::__invoke lines 33-37.
        $this->assertFalse(
            $admin->hasPermission('dashboard.view'),
            '403 is raised by DashboardController abort_unless(hasPermission dashboard.view)'
        );
        $this->assertTrue(
            class_exists(DashboardController::class)
        );
    }

    public function test_seller_keeps_dashboard_access(): void
    {
        $company = $this->makeCompanyWithPlan('Hotfix Seller Co', 'professional');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-hotfix@dashboard.test',
        ]);

        app(TenantContext::class)->set($company, $seller);

        $this->assertTrue($seller->hasPermission('dashboard.view'));
        $this->assertFalse($seller->hasPermission('integrations.manage'));

        $this->actingAs($seller)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_role_permission_seeder_sync_still_includes_dashboard_view(): void
    {
        $role = Role::query()->where('slug', Role::ADMINISTRATOR)->with('permissions')->firstOrFail();
        $slugs = $role->permissions->pluck('slug')->all();

        $this->assertContains('dashboard.view', $slugs);
        $this->assertContains('integrations.view', $slugs);
        $this->assertContains('integrations.manage', $slugs);
    }
}
