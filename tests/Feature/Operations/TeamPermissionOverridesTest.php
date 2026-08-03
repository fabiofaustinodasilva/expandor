<?php

namespace Tests\Feature\Operations;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Company\Support\CommercialProfileCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class TeamPermissionOverridesTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        CommercialProfileCatalog::ensurePermissionRecords();
    }

    public function test_new_seller_inherits_role_permissions_without_overrides(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Perm Base');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-base@perm.test']);
        $sellerRole = Role::query()->where('slug', Role::SELLER)->firstOrFail();

        $this->actingAs($manager)->post(route('operations.team.store'), [
            'name' => 'Seller Base',
            'email' => 'seller-base@perm.test',
            'password' => 'password123',
            'role_id' => $sellerRole->id,
        ])->assertRedirect();

        $seller = User::query()->where('email', 'seller-base@perm.test')->firstOrFail();
        $seller->load(['role.permissions', 'permissionOverrides']);

        $this->assertTrue($seller->hasPermission('maps.view'));
        $this->assertTrue($seller->hasPermission('properties.create'));
        $this->assertFalse($seller->hasPermission('users.create'));
        $this->assertFalse($seller->hasPermission('dashboard.team'));
        $this->assertCount(0, $seller->permissionOverrides);
    }

    public function test_manager_can_grant_and_deny_admin_permissions_with_audit(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Perm Override');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-ov@perm.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-ov@perm.test']);
        $seller->load('role.permissions');

        $this->assertFalse($seller->hasPermission('users.create'));
        $this->assertFalse($seller->hasPermission('reports.view'));

        $payload = [];
        foreach (CommercialProfileCatalog::commercialPermissionSlugs() as $slug) {
            $payload[$slug] = $seller->hasPermission($slug) ? '1' : '0';
        }
        $payload['users.create'] = '1';
        $payload['reports.view'] = '1';

        $this->actingAs($manager)
            ->put(route('operations.team.permissions', $seller), ['permissions' => $payload])
            ->assertRedirect();

        $seller->refresh()->load('permissionOverrides');
        $this->assertTrue($seller->hasPermission('users.create'));
        $this->assertTrue($seller->hasPermission('reports.view'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.permission_override_granted',
            'auditable_id' => $seller->id,
            'company_id' => $company->id,
        ]);

        $summary = CommercialProfileCatalog::overrideSummary($seller);
        $this->assertSame('customized', $summary['mode']);
        $this->assertGreaterThanOrEqual(2, $summary['overrides_count']);
    }

    public function test_clearing_override_returns_to_role_baseline(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Perm Clear');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-clear@perm.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-clear@perm.test']);

        $perm = Permission::query()->where('slug', 'reports.view')->firstOrFail();
        $seller->permissionOverrides()->attach($perm->id, ['effect' => 'grant']);
        $this->assertTrue($seller->fresh()->hasPermission('reports.view'));

        $payload = [];
        foreach (CommercialProfileCatalog::commercialPermissionSlugs() as $slug) {
            $payload[$slug] = '0';
        }

        $this->actingAs($manager)
            ->put(route('operations.team.permissions', $seller), ['permissions' => $payload])
            ->assertRedirect();

        $seller->refresh()->load('permissionOverrides');
        $this->assertFalse($seller->hasPermission('reports.view'));
        $this->assertFalse(
            $seller->permissionOverrides->contains('slug', 'reports.view')
        );
        $this->assertTrue(
            AuditLog::query()->where('action', 'team.permission_override_cleared')->where('auditable_id', $seller->id)->exists()
        );
    }

    public function test_permission_overrides_are_tenant_isolated(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Perm A');
        $companyB = $this->makeCompanyWithPlan('Empresa Perm B');
        $managerA = $this->makeUser($companyA, Role::MANAGER, ['email' => 'mgr-a@perm.test']);
        $sellerB = $this->makeUser($companyB, Role::SELLER, ['email' => 'seller-b@perm.test']);

        $this->actingAs($managerA)
            ->put(route('operations.team.permissions', $sellerB), [
                'permissions' => ['users.create' => '1'],
            ])
            ->assertNotFound();
    }

    public function test_operational_core_ignores_deny_override(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Perm Effective');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-eff@perm.test']);
        $perm = Permission::query()->where('slug', 'maps.view')->firstOrFail();

        $this->assertTrue($seller->hasPermission('maps.view'));
        $seller->permissionOverrides()->attach($perm->id, ['effect' => 'deny']);
        $this->assertTrue($seller->fresh()->load('permissionOverrides')->hasPermission('maps.view'));
    }

    public function test_permissions_ui_excludes_operational_labels(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Perm UI');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-ui@perm.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-ui@perm.test']);

        $this->actingAs($manager)
            ->get(route('operations.team', ['member' => $seller->id, 'panel' => 'permissions']))
            ->assertOk()
            ->assertSee('Perfil padrão')
            ->assertSee('Criar vendedor')
            ->assertSee('Dashboard Gerencial')
            ->assertDontSee('Ver mapa')
            ->assertDontSee('Registrar visita')
            ->assertDontSee('Visualizar pontos da equipe');
    }
}
