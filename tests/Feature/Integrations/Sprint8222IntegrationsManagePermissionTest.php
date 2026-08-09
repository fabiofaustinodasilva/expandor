<?php

namespace Tests\Feature\Integrations;

use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Role;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Tenancy\TenantContext;
use Database\Seeders\EnsureIntegrationsPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint8222IntegrationsManagePermissionTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_administrator_with_manage_sees_configure_cta(): void
    {
        $company = $this->makeCompanyWithPlan('Admin Manage Co', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);
        $admin->load(['role.permissions', 'permissionOverrides']);

        $this->assertTrue($admin->hasPermission('integrations.view'));
        $this->assertTrue($admin->hasPermission('integrations.manage'));

        $this->actingAs($admin)
            ->get(route('operations.integrations'))
            ->assertOk()
            ->assertSee('Configurar', false)
            ->assertDontSee('Somente visualização', false);
    }

    public function test_administrator_without_manage_sees_view_only(): void
    {
        $company = $this->makeCompanyWithPlan('Admin ViewOnly Co', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $role = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();
        $manageId = Permission::query()->where('slug', 'integrations.manage')->value('id');
        $this->assertNotNull($manageId);
        $role->permissions()->detach($manageId);

        $admin->unsetRelation('role');
        $admin->load(['role.permissions', 'permissionOverrides']);
        $this->assertTrue($admin->hasPermission('integrations.view'));
        $this->assertFalse($admin->hasPermission('integrations.manage'));

        $this->actingAs($admin)
            ->get(route('operations.integrations'))
            ->assertOk()
            ->assertSee('Somente visualização', false)
            ->assertDontSee('>Configurar<', false);
    }

    public function test_additive_seeder_restores_manage_on_existing_administrator_role(): void
    {
        $role = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();
        $manageId = Permission::query()->where('slug', 'integrations.manage')->value('id');
        $viewId = Permission::query()->where('slug', 'integrations.view')->value('id');
        $this->assertNotNull($manageId);
        $this->assertNotNull($viewId);

        // Simulate production: view present, manage missing after incomplete sync.
        $role->permissions()->detach($manageId);
        $this->assertFalse($role->fresh()->permissions()->where('permissions.id', $manageId)->exists());
        $this->assertTrue($role->fresh()->permissions()->where('permissions.id', $viewId)->exists());

        (new EnsureIntegrationsPermissionsSeeder)->run();

        $this->assertTrue($role->fresh()->permissions()->where('permissions.id', $manageId)->exists());
        $this->assertTrue($role->fresh()->permissions()->where('permissions.id', $viewId)->exists());

        $company = $this->makeCompanyWithPlan('Repair Co', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);
        $admin->load(['role.permissions', 'permissionOverrides']);

        $this->assertTrue($admin->hasPermission('integrations.manage'));

        $this->actingAs($admin)
            ->get(route('operations.integrations'))
            ->assertOk()
            ->assertSee('Configurar', false)
            ->assertDontSee('Somente visualização', false);
    }

    public function test_seller_cannot_configure_integrations(): void
    {
        $company = $this->makeCompanyWithPlan('Seller No Manage', 'enterprise');
        $seller = $this->makeUser($company, Role::SELLER);
        app(TenantContext::class)->set($company, $seller);

        $this->assertFalse($seller->hasPermission('integrations.manage'));
        $this->assertFalse($seller->hasPermission('integrations.view'));

        $this->actingAs($seller)
            ->get(route('operations.integrations'))
            ->assertForbidden();

        $this->actingAs($seller)
            ->put(route('operations.integrations.google-maps.update'), [
                'browser_api_key' => 'AIzaSySellerShouldNotSaveKey1234567890',
            ])
            ->assertForbidden();
    }

    public function test_cross_tenant_cannot_manage_foreign_integration(): void
    {
        $companyA = $this->makeCompanyWithPlan('Tenant A', 'enterprise');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($companyA, $adminA);

        $integration = CompanyIntegration::query()->create([
            'company_id' => $companyA->id,
            'provider' => 'google_maps',
            'category' => 'maps',
            'enabled' => true,
            'status' => 'connected',
            'credentials' => ['browser_api_key' => 'AIzaSyCrossTenantKeyValue1234567890AA'],
        ]);

        $companyB = $this->makeCompanyWithPlan('Tenant B', 'enterprise');
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($companyB, $adminB);

        $this->assertFalse($adminB->can('update', $integration));
        $this->assertFalse($adminB->can('delete', $integration));
    }

    public function test_ui_condition_uses_can_manage_from_integrations_manage(): void
    {
        $blade = file_get_contents(resource_path('views/operations/integrations.blade.php'));
        $this->assertStringContainsString('@elseif($canManage)', $blade);
        $this->assertStringContainsString('Somente visualização', $blade);

        $controller = file_get_contents(app_path('Http/Controllers/Web/Operations/IntegrationsController.php'));
        $this->assertStringContainsString("hasPermission('integrations.manage')", $controller);
    }
}
