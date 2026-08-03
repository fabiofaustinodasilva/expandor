<?php

namespace Tests\Feature\Security;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Security\Services\PrivacyService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SecurityModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_user_cannot_access_audit_from_another_tenant(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Audit A');
        $companyB = $this->makeCompanyWithPlan('Empresa Audit B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@security.test',
        ]);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, [
            'email' => 'admin-b@security.test',
        ]);

        AuditLog::query()->withoutGlobalScopes()->create([
            'company_id' => $companyA->id,
            'user_id' => $adminA->id,
            'action' => 'test.action.alpha',
            'new_values' => ['secret' => 'alpha-only'],
            'ip' => '10.0.0.1',
            'created_at' => now(),
        ]);

        AuditLog::query()->withoutGlobalScopes()->create([
            'company_id' => $companyB->id,
            'user_id' => $adminB->id,
            'action' => 'test.action.beta',
            'new_values' => ['secret' => 'beta-only'],
            'ip' => '10.0.0.2',
            'created_at' => now(),
        ]);

        $this->actingAs($adminA)
            ->get(route('company.audit.index'))
            ->assertOk()
            ->assertSee('test.action.alpha')
            ->assertDontSee('test.action.beta')
            ->assertDontSee('beta-only');
    }

    public function test_export_respects_tenant(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Export A');
        $companyB = $this->makeCompanyWithPlan('Empresa Export B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-export-a@security.test',
        ]);

        Resident::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Morador Alpha Export',
        ]);
        Resident::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Morador Beta Export',
        ]);

        app(TenantContext::class)->set($companyA, $adminA);

        $payload = app(PrivacyService::class)->exportPayloadForCompany($companyA, $adminA);

        $names = collect($payload['residents'])->pluck('name')->all();

        $this->assertContains('Morador Alpha Export', $names);
        $this->assertNotContains('Morador Beta Export', $names);
        $this->assertSame($companyA->id, $payload['company']['id']);

        $this->actingAs($adminA)
            ->post(route('company.privacy.export'))
            ->assertRedirect(route('company.privacy.index'));

        $this->assertDatabaseHas('data_export_requests', [
            'company_id' => $companyA->id,
            'requested_by' => $adminA->id,
            'status' => 'ready',
        ]);

        $this->assertDatabaseMissing('data_export_requests', [
            'company_id' => $companyB->id,
        ]);
    }

    public function test_rate_limit_works_on_login(): void
    {
        RateLimiter::clear('login');

        config(['security.login.max_attempts' => 3]);

        // Re-bind limiter with the updated config for this process.
        RateLimiter::for('login', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(3)
                ->by('rate-limit-test|'.$request->ip());
        });

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('login.store'), [
                'email' => 'nobody@security.test',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => 'nobody@security.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_permissions_still_work(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Perm Security');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-perm@security.test',
        ]);
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer-perm@security.test',
        ]);

        $this->actingAs($admin)
            ->get(route('company.audit.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('company.privacy.index'))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('company.audit.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('company.privacy.index'))
            ->assertForbidden();
    }
}
