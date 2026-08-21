<?php

namespace Tests\Feature\Platform;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Models\CompanyFeatureFlag;
use App\Domains\Platform\Models\CompanyHealthScore;
use App\Domains\Platform\Models\FeatureFlag;
use App\Domains\Platform\Models\ImpersonationSession;
use App\Domains\Platform\Services\FeatureFlagService;
use App\Domains\Platform\Services\HealthScoreService;
use App\Domains\Sales\Properties\Models\Property;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\Support\PlatformCompanyStorePayload;
use Tests\TestCase;

class PlatformAdminModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use PlatformCompanyStorePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_platform_admin_can_access_panel(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('Painel Expandor')
            ->assertSee('Empresas ativas')
            ->assertSee('Saúde dos clientes');

        $this->actingAs($owner)
            ->get(route('platform.companies.index'))
            ->assertOk()
            ->assertSee('Empresas clientes');
    }

    public function test_company_admin_receives_forbidden_on_platform(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Cliente Platform');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@client-platform.test',
        ]);

        $this->assertFalse($admin->isPlatformAdmin());
        $this->assertFalse($admin->hasPermission('platform.access'));

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('platform.companies.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('platform.companies.create'))
            ->assertForbidden();
    }

    public function test_tenant_isolation_still_works_for_company_admins(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Isolamento A');
        $companyB = $this->makeCompanyWithPlan('Empresa Isolamento B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@iso-platform.test',
        ]);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, [
            'email' => 'admin-b@iso-platform.test',
        ]);

        Property::factory()->create(['company_id' => $companyA->id]);
        Property::factory()->count(2)->create(['company_id' => $companyB->id]);

        app(TenantContext::class)->set($companyA, $adminA);
        $this->assertSame(1, Property::query()->count());

        app(TenantContext::class)->set($companyB, $adminB);
        $this->assertSame(2, Property::query()->count());

        $owner = $this->makePlatformAdmin();
        $this->actingAs($owner)
            ->get(route('platform.companies.index'))
            ->assertOk()
            ->assertSee('Empresa Isolamento A')
            ->assertSee('Empresa Isolamento B');
    }

    public function test_platform_can_create_company_with_admin(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = \App\Domains\Company\Models\Plan::query()->where('slug', 'professional')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('platform.companies.store'), $this->platformCompanyStorePayload($plan->id, [
                'company_name' => 'Nova Empresa Platform',
                'company_email' => 'contato@nova-platform.test',
                'admin_name' => 'Admin Novo',
                'admin_email' => 'admin@nova-platform.test',
                'admin_password' => 'password',
                'admin_password_confirmation' => 'password',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'name' => 'Nova Empresa Platform',
            'email' => 'contato@nova-platform.test',
            'is_system' => 0,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@nova-platform.test',
            'is_platform_admin' => 0,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'platform.company.created',
        ]);

        $company = Company::query()->where('email', 'contato@nova-platform.test')->firstOrFail();
        $this->assertDatabaseHas('company_health_scores', [
            'company_id' => $company->id,
        ]);

        $admin = User::query()->withoutGlobalScopes()->where('email', 'admin@nova-platform.test')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertForbidden();
    }

    public function test_feature_flags_impersonation_and_health_score(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Console');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@console-platform.test',
        ]);

        $flag = FeatureFlag::query()->where('key', 'ai.enabled')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('platform.flags.update', $company), [
                'key' => 'ai.enabled',
                'enabled' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('company_feature_flags', [
            'company_id' => $company->id,
            'feature_flag_id' => $flag->id,
            'enabled' => 0,
        ]);

        $this->assertFalse(app(FeatureFlagService::class)->isEnabled($company, 'ai.enabled'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'platform.feature_flag.toggled',
            'company_id' => $company->id,
        ]);

        $this->actingAs($owner)
            ->post(route('platform.health.recalculate', $company))
            ->assertRedirect();

        $this->assertInstanceOf(CompanyHealthScore::class, CompanyHealthScore::query()->where('company_id', $company->id)->first());
        $this->assertGreaterThanOrEqual(0, app(HealthScoreService::class)->forCompany($company)->score);

        $this->actingAs($owner)
            ->post(route('platform.impersonation.store', $company), [
                'user_id' => $admin->id,
                'reason' => 'Suporte teste',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull(ImpersonationSession::query()->whereNull('ended_at')->first());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'platform.impersonation.started',
        ]);

        $this->post(route('impersonation.exit'))
            ->assertRedirect(route('platform.dashboard'));

        $this->assertAuthenticatedAs($owner);
        $this->assertSame(0, ImpersonationSession::query()->whereNull('ended_at')->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'platform.impersonation.ended',
        ]);

        $this->actingAs($owner)
            ->post(route('platform.impersonation.store', $company), [
                'user_id' => $owner->id,
                'reason' => 'Tentativa inválida',
            ])
            ->assertSessionHasErrors();

        $this->actingAs($admin)
            ->get(route('platform.flags.index'))
            ->assertForbidden();
    }

    public function test_company_suspend_and_activate_generate_audit(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Suspend');

        $this->actingAs($owner)
            ->post(route('platform.companies.suspend', $company), [
                'reason' => 'Inadimplência',
            ])
            ->assertRedirect();

        $this->assertSame(Company::STATUS_SUSPENDED, $company->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'platform.company.suspended',
            'company_id' => $company->id,
        ]);

        $this->actingAs($owner)
            ->post(route('platform.companies.activate', $company))
            ->assertRedirect();

        $this->assertSame(Company::STATUS_ACTIVE, $company->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'platform.company.activated',
            'company_id' => $company->id,
        ]);
    }
}
